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
function stockSupplierItem(array $attributes = []): SupplierItem
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
 * The period for the current calendar month, which the ledger reads.
 */
function currentStockPeriod(): Period
{
    return Period::factory()->create([
        'year' => (int) now()->format('Y'),
        'month' => (int) now()->format('n'),
        'status' => 'open',
    ]);
}

test('a staff profile can view the stock ledger with a running balance', function () {
    $staff = Profile::factory()->create();
    $supplierItem = stockSupplierItem();
    $period = currentStockPeriod();

    Transaction::factory()->create([
        'period_id' => $period->id,
        'supplier_item_id' => $supplierItem->id,
        'type' => 'received',
        'quantity' => 10,
        'unit_cost' => 100,
        'total_cost' => 1000,
        'transaction_date' => now()->startOfMonth()->toDateString(),
        'profile_id' => $staff->id,
    ]);

    Transaction::factory()->create([
        'period_id' => $period->id,
        'supplier_item_id' => $supplierItem->id,
        'type' => 'consumption',
        'quantity' => 3,
        'unit_cost' => 100,
        'total_cost' => 300,
        'transaction_date' => now()->startOfMonth()->addDay()->toDateString(),
        'profile_id' => $staff->id,
    ]);

    $response = $this->withSession(['active_profile_id' => $staff->id])
        ->get(route('stock.show', $supplierItem->id));

    $response->assertOk();
    $response->assertInertia(function ($page) use ($supplierItem) {
        $page->component('Stock');
        $page->where('supplierItem.id', $supplierItem->id);
        $page->where('beginning.quantity', 0);
        $page->where('ending.quantity', 7);
        $page->has('transactions', 2);
        $page->where('transactions.0.running_quantity', 10);
        $page->where('transactions.1.running_quantity', 7);
    });
});

test('the beginning balance carries forward from the previous closed period', function () {
    $staff = Profile::factory()->create();
    $supplierItem = stockSupplierItem();

    $previous = Period::factory()->create([
        'year' => (int) now()->subMonthNoOverflow()->format('Y'),
        'month' => (int) now()->subMonthNoOverflow()->format('n'),
        'status' => 'closed',
    ]);

    PeriodBalance::factory()->create([
        'period_id' => $previous->id,
        'supplier_item_id' => $supplierItem->id,
        'beginning_quantity' => 0,
        'beginning_cost' => 0,
        'ending_quantity' => 5,
        'ending_cost' => 500,
    ]);

    $period = currentStockPeriod();

    Transaction::factory()->create([
        'period_id' => $period->id,
        'supplier_item_id' => $supplierItem->id,
        'type' => 'received',
        'quantity' => 4,
        'unit_cost' => 100,
        'total_cost' => 400,
        'transaction_date' => now()->startOfMonth()->toDateString(),
        'profile_id' => $staff->id,
    ]);

    $this->withSession(['active_profile_id' => $staff->id])
        ->get(route('stock.show', $supplierItem->id))
        ->assertInertia(function ($page) {
            $page->where('beginning.quantity', 5);
            $page->where('ending.quantity', 9);
            $page->where('transactions.0.running_quantity', 9);
        });
});

test('a reversal nets to zero in the running balance', function () {
    $staff = Profile::factory()->create();
    $supplierItem = stockSupplierItem();
    $period = currentStockPeriod();

    $original = Transaction::factory()->create([
        'period_id' => $period->id,
        'supplier_item_id' => $supplierItem->id,
        'type' => 'received',
        'quantity' => 10,
        'unit_cost' => 100,
        'total_cost' => 1000,
        'transaction_date' => now()->startOfMonth()->toDateString(),
        'profile_id' => $staff->id,
    ]);

    Transaction::factory()->create([
        'period_id' => $period->id,
        'supplier_item_id' => $supplierItem->id,
        'type' => 'received',
        'quantity' => 10,
        'unit_cost' => 100,
        'total_cost' => 1000,
        'transaction_date' => now()->startOfMonth()->addDay()->toDateString(),
        'profile_id' => $staff->id,
        'reverses_transaction_id' => $original->id,
    ]);

    $this->withSession(['active_profile_id' => $staff->id])
        ->get(route('stock.show', $supplierItem->id))
        ->assertInertia(function ($page) {
            $page->where('ending.quantity', 0);
            $page->where('transactions.0.running_quantity', 10);
            $page->where('transactions.1.running_quantity', 0);
            $page->where('transactions.1.is_reversal', true);
        });
});

test('every role can view the stock ledger', function (string $role) {
    $profile = Profile::factory()->create(['role' => $role]);
    $supplierItem = stockSupplierItem();

    $this->withSession(['active_profile_id' => $profile->id])
        ->get(route('stock.show', $supplierItem->id))
        ->assertOk();
})->with(['staff', 'supervisor', 'administrator']);

test('an unknown supplier item returns a 404', function () {
    $staff = Profile::factory()->create();

    $this->withSession(['active_profile_id' => $staff->id])
        ->get(route('stock.show', 999))
        ->assertNotFound();
});
