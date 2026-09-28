<?php

use App\Models\Item;
use App\Models\Period;
use App\Models\PeriodBalance;
use App\Models\Profile;
use App\Models\Supplier;
use App\Models\SupplierItem;
use App\Models\Transaction;

/**
 * Create an active supplier item with a contract price in effect.
 */
function reportSupplierItem(array $attributes = []): SupplierItem
{
    $supplier = Supplier::factory()->create();
    $item = Item::factory()->create();

    return SupplierItem::factory()->create(array_merge([
        'supplier_id' => $supplier->id,
        'item_id' => $item->id,
        'price' => 100,
        'effective_date' => '2026-01-01',
        'active' => true,
    ], $attributes));
}

/**
 * Record a movement in a period for a supplier item.
 */
function reportMovement(Period $period, SupplierItem $supplierItem, Profile $profile, string $type, float $quantity, float $totalCost, array $attributes = []): Transaction
{
    return Transaction::factory()->create(array_merge([
        'period_id' => $period->id,
        'supplier_item_id' => $supplierItem->id,
        'type' => $type,
        'quantity' => $quantity,
        'unit_cost' => 100,
        'total_cost' => $totalCost,
        'transaction_date' => sprintf('%04d-%02d-05', $period->year, $period->month),
        'profile_id' => $profile->id,
    ], $attributes));
}

test('a supervisor can view the monthly report', function () {
    $supervisor = Profile::factory()->supervisor()->create();
    $period = Period::factory()->create(['year' => 2026, 'month' => 1]);

    $response = $this->withSession(['active_profile_id' => $supervisor->id])
        ->get(route('reports.index'));

    $response->assertOk();
    $response->assertInertia(function ($page) use ($period) {
        $page->component('Reports');
        $page->has('periods', 1);
        $page->where('selectedPeriodId', $period->id);
        $page->where('report.period.id', $period->id);
    });
});

test('a staff profile cannot view the monthly report', function () {
    $staff = Profile::factory()->create();

    $this->withSession(['active_profile_id' => $staff->id])
        ->get(route('reports.index'))
        ->assertForbidden();
});

test('the report defaults to the most recent period', function () {
    $supervisor = Profile::factory()->supervisor()->create();
    Period::factory()->create(['year' => 2026, 'month' => 1]);
    $february = Period::factory()->create(['year' => 2026, 'month' => 2]);

    $this->withSession(['active_profile_id' => $supervisor->id])
        ->get(route('reports.index'))
        ->assertInertia(fn ($page) => $page->where('selectedPeriodId', $february->id));
});

test('the report can be generated for a requested period', function () {
    $supervisor = Profile::factory()->supervisor()->create();
    $january = Period::factory()->create(['year' => 2026, 'month' => 1]);
    Period::factory()->create(['year' => 2026, 'month' => 2]);

    $this->withSession(['active_profile_id' => $supervisor->id])
        ->get(route('reports.index', ['period_id' => $january->id]))
        ->assertInertia(fn ($page) => $page->where('selectedPeriodId', $january->id));
});

test('an open period derives movement columns and ending balance live', function () {
    $supervisor = Profile::factory()->supervisor()->create();
    $period = Period::factory()->create(['year' => 2026, 'month' => 1]);
    $supplierItem = reportSupplierItem();

    reportMovement($period, $supplierItem, $supervisor, 'received', 10, 1000);
    reportMovement($period, $supplierItem, $supervisor, 'consumption', 3, 300);

    $this->withSession(['active_profile_id' => $supervisor->id])
        ->get(route('reports.index'))
        ->assertInertia(function ($page) {
            $page->where('report.groups.0.rows.0.beginning.quantity', 0);
            $page->where('report.groups.0.rows.0.received.quantity', 10);
            $page->where('report.groups.0.rows.0.received.total_cost', 1000);
            $page->where('report.groups.0.rows.0.consumption.quantity', 3);
            $page->where('report.groups.0.rows.0.ending.quantity', 7);
            $page->where('report.groups.0.rows.0.ending.total_cost', 700);
        });
});

test('a closed period reads its frozen snapshot for beginning and ending', function () {
    $supervisor = Profile::factory()->supervisor()->create();
    $period = Period::factory()->create([
        'year' => 2026,
        'month' => 1,
        'status' => 'closed',
        'closed_at' => now(),
        'closed_by' => $supervisor->id,
    ]);
    $supplierItem = reportSupplierItem();

    // The snapshot deliberately differs from what the transactions would
    // compute, so the test proves the frozen figures are used (FR-7.4).
    PeriodBalance::factory()->create([
        'period_id' => $period->id,
        'supplier_item_id' => $supplierItem->id,
        'beginning_quantity' => 5,
        'beginning_cost' => 500,
        'ending_quantity' => 8,
        'ending_cost' => 800,
    ]);

    reportMovement($period, $supplierItem, $supervisor, 'received', 10, 1000);

    $this->withSession(['active_profile_id' => $supervisor->id])
        ->get(route('reports.index'))
        ->assertInertia(function ($page) {
            $page->where('report.groups.0.rows.0.beginning.quantity', 5);
            $page->where('report.groups.0.rows.0.beginning.total_cost', 500);
            $page->where('report.groups.0.rows.0.ending.quantity', 8);
            $page->where('report.groups.0.rows.0.ending.total_cost', 800);
            // Movement columns are still aggregated from the transactions.
            $page->where('report.groups.0.rows.0.received.quantity', 10);
        });
});

test('a reversal nets to zero within its movement column', function () {
    $supervisor = Profile::factory()->supervisor()->create();
    $period = Period::factory()->create(['year' => 2026, 'month' => 1]);
    $supplierItem = reportSupplierItem();

    $original = reportMovement($period, $supplierItem, $supervisor, 'received', 10, 1000);
    reportMovement($period, $supplierItem, $supervisor, 'received', 10, 1000, [
        'reverses_transaction_id' => $original->id,
    ]);

    $this->withSession(['active_profile_id' => $supervisor->id])
        ->get(route('reports.index'))
        ->assertInertia(function ($page) {
            $page->where('report.groups.0.rows.0.received.quantity', 0);
            $page->where('report.groups.0.rows.0.received.total_cost', 0);
            $page->where('report.groups.0.rows.0.ending.quantity', 0);
        });
});

test('the report groups by supplier with subtotals and a grand total', function () {
    $supervisor = Profile::factory()->supervisor()->create();
    $period = Period::factory()->create(['year' => 2026, 'month' => 1]);

    $supplier = Supplier::factory()->create(['name' => 'Acme Nutrition']);
    $first = reportSupplierItem(['supplier_id' => $supplier->id]);
    $second = reportSupplierItem(['supplier_id' => $supplier->id]);

    reportMovement($period, $first, $supervisor, 'received', 10, 1000);
    reportMovement($period, $second, $supervisor, 'received', 4, 400);

    $this->withSession(['active_profile_id' => $supervisor->id])
        ->get(route('reports.index'))
        ->assertInertia(function ($page) {
            $page->has('report.groups', 1);
            $page->where('report.groups.0.supplier_name', 'Acme Nutrition');
            $page->has('report.groups.0.rows', 2);
            $page->where('report.groups.0.subtotal.received.quantity', 14);
            $page->where('report.groups.0.subtotal.received.total_cost', 1400);
            $page->where('report.grand_total.received.quantity', 14);
            $page->where('report.grand_total.received.total_cost', 1400);
        });
});
