<?php

use App\Models\Batch;
use App\Models\Item;
use App\Models\Period;
use App\Models\Profile;
use App\Models\Supplier;
use App\Models\SupplierItem;
use App\Models\Transaction;
use Illuminate\Support\Carbon;

/**
 * Create an active supplier item with a contract price in effect.
 */
function dashboardSupplierItem(array $attributes = []): SupplierItem
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
function dashboardMovement(Period $period, SupplierItem $supplierItem, Profile $profile, string $type, float $quantity, array $attributes = []): Transaction
{
    return Transaction::factory()->create(array_merge([
        'period_id' => $period->id,
        'supplier_item_id' => $supplierItem->id,
        'type' => $type,
        'quantity' => $quantity,
        'unit_cost' => 100,
        'total_cost' => $quantity * 100,
        'transaction_date' => now()->toDateString(),
        'profile_id' => $profile->id,
    ], $attributes));
}

/**
 * Create a batch with an expiration date relative to today.
 */
function dashboardBatch(int $days, array $attributes = []): Batch
{
    $supplierItem = dashboardSupplierItem();

    return Batch::factory()->create(array_merge([
        'supplier_item_id' => $supplierItem->id,
        'expiration_date' => Carbon::today()->addDays($days)->toDateString(),
        'status' => Batch::STATUS_ACTIVE,
    ], $attributes));
}

test('the dashboard is reachable with an active profile', function () {
    $profile = Profile::factory()->create();

    $this->withSession(['active_profile_id' => $profile->id])
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('alerts')
            ->has('summary')
            ->has('recentTransactions'));
});

test('a staff profile sees only operational alerts', function () {
    $staff = Profile::factory()->create();

    $this->withSession(['active_profile_id' => $staff->id])
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->has('alerts.expired_batches')
            ->has('alerts.near_expiry_batches')
            ->has('alerts.damaged_batches')
            ->has('alerts.negative_balances')
            ->has('alerts.low_stock_items')
            ->missing('alerts.open_periods_to_close')
            ->missing('alerts.override_transactions')
            ->missing('alerts.missing_contract_prices'));
});

test('a supervisor additionally sees the period-close alert', function () {
    $supervisor = Profile::factory()->supervisor()->create();

    $this->withSession(['active_profile_id' => $supervisor->id])
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->has('alerts.open_periods_to_close')
            ->missing('alerts.override_transactions')
            ->missing('alerts.missing_contract_prices'));
});

test('an administrator sees every alert', function () {
    $admin = Profile::factory()->administrator()->create();

    $this->withSession(['active_profile_id' => $admin->id])
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->has('alerts.expired_batches')
            ->has('alerts.near_expiry_batches')
            ->has('alerts.damaged_batches')
            ->has('alerts.negative_balances')
            ->has('alerts.low_stock_items')
            ->has('alerts.open_periods_to_close')
            ->has('alerts.override_transactions')
            ->has('alerts.missing_contract_prices'));
});

test('the dashboard counts expired, near-expiry and damaged batches', function () {
    $staff = Profile::factory()->create();

    dashboardBatch(-5);
    dashboardBatch(30);
    dashboardBatch(200);
    dashboardBatch(-5, ['status' => Batch::STATUS_DAMAGED]);

    $this->withSession(['active_profile_id' => $staff->id])
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('alerts.expired_batches', 1)
            ->where('alerts.near_expiry_batches', 1)
            ->where('alerts.damaged_batches', 1));
});

test('the dashboard counts negative balances in the open period', function () {
    $staff = Profile::factory()->create();
    $period = Period::factory()->create();
    $supplierItem = dashboardSupplierItem();

    dashboardMovement($period, $supplierItem, $staff, 'consumption', 5);

    $this->withSession(['active_profile_id' => $staff->id])
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('alerts.negative_balances', 1));
});

test('the dashboard counts low-stock items but ignores never-stocked ones', function () {
    $staff = Profile::factory()->create();
    $period = Period::factory()->create();

    $low = dashboardSupplierItem();
    dashboardMovement($period, $low, $staff, 'received', 5);

    $healthy = dashboardSupplierItem();
    dashboardMovement($period, $healthy, $staff, 'received', 100);

    // An active item that has never been transacted is not flagged.
    dashboardSupplierItem();

    $this->withSession(['active_profile_id' => $staff->id])
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('alerts.low_stock_items', 1));
});

test('the dashboard counts open periods whose month has ended', function () {
    $supervisor = Profile::factory()->supervisor()->create();

    $previous = now()->subMonthNoOverflow();
    Period::factory()->create([
        'year' => (int) $previous->format('Y'),
        'month' => (int) $previous->format('n'),
        'status' => 'open',
    ]);

    // The current month is not yet ready to close.
    Period::factory()->create(['status' => 'open']);

    $this->withSession(['active_profile_id' => $supervisor->id])
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('alerts.open_periods_to_close', 1));
});

test('the dashboard counts overrides that have not been reversed', function () {
    $admin = Profile::factory()->administrator()->create();
    $period = Period::factory()->create();
    $supplierItem = dashboardSupplierItem();

    dashboardMovement($period, $supplierItem, $admin, 'received', 10);
    dashboardMovement($period, $supplierItem, $admin, 'consumption', 20, [
        'override_reason' => 'Emergency ward issue',
    ]);

    // A reversed override no longer needs review.
    $reversed = dashboardMovement($period, $supplierItem, $admin, 'consumption', 1, [
        'override_reason' => 'Mistake',
    ]);
    dashboardMovement($period, $supplierItem, $admin, 'consumption', 1, [
        'reverses_transaction_id' => $reversed->id,
    ]);

    $this->withSession(['active_profile_id' => $admin->id])
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('alerts.override_transactions', 1));
});

test('the dashboard counts supplier items with no contract price in effect', function () {
    $admin = Profile::factory()->administrator()->create();

    // An active row whose effective date is in the future has no price in
    // effect yet, so it cannot be transacted against (FR-2.4).
    dashboardSupplierItem(['effective_date' => now()->addMonth()->toDateString()]);

    // A pair with a price in effect is not flagged.
    dashboardSupplierItem();

    $this->withSession(['active_profile_id' => $admin->id])
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('alerts.missing_contract_prices', 1));
});

test('the dashboard summarises the open period', function () {
    $staff = Profile::factory()->create();
    $period = Period::factory()->create();
    $supplierItem = dashboardSupplierItem();

    dashboardMovement($period, $supplierItem, $staff, 'received', 10);

    $this->withSession(['active_profile_id' => $staff->id])
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('summary.period.year', (int) now()->format('Y'))
            ->where('summary.period.month', (int) now()->format('n'))
            ->where('summary.period.status', 'open')
            ->where('summary.active_supplier_items', 1)
            ->where('summary.stock_value', 1000)
            ->where('summary.transactions_this_period', 1)
            ->where('summary.near_expiry_days', 90)
            ->where('summary.low_stock_threshold', 10));
});

test('the dashboard reports no period when none exists', function () {
    $staff = Profile::factory()->create();

    $this->withSession(['active_profile_id' => $staff->id])
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('summary.period', null)
            ->where('summary.transactions_this_period', 0)
            ->where('summary.stock_value', 0));
});

test('the dashboard shows at most ten recent transactions', function () {
    $staff = Profile::factory()->create();
    $period = Period::factory()->create();
    $supplierItem = dashboardSupplierItem();

    foreach (range(1, 12) as $index) {
        dashboardMovement($period, $supplierItem, $staff, 'received', $index);
    }

    $this->withSession(['active_profile_id' => $staff->id])
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->has('recentTransactions', 10));
});
