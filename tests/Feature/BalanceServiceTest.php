<?php

use App\Models\Period;
use App\Models\PeriodBalance;
use App\Models\Profile;
use App\Models\SupplierItem;
use App\Models\Transaction;
use App\Services\BalanceService;

test('computes balance for a single received transaction', function () {
    $supplierItem = SupplierItem::factory()->create(['price' => 100, 'effective_date' => '2026-01-01']);
    $profile = Profile::factory()->create();

    Transaction::factory()->create([
        'period_id' => 1,
        'supplier_item_id' => $supplierItem->id,
        'type' => 'received',
        'quantity' => 10,
        'unit_cost' => 100,
        'total_cost' => 1000,
        'transaction_date' => '2026-01-05',
        'profile_id' => $profile->id,
    ]);

    $balance = (new BalanceService)->balanceFor($supplierItem->id, 1);

    expect($balance['quantity'])->toBe(10.0);
    expect($balance['total_cost'])->toBe(1000.0);
});

test('applies each movement type with the correct sign', function () {
    $supplierItem = SupplierItem::factory()->create(['price' => 50, 'effective_date' => '2026-01-01']);
    $profile = Profile::factory()->create();

    $movements = [
        ['received', 100, 5000],
        ['consumption', 30, 1500],
        ['return_from_ward', 5, 250],
        ['return_to_supplier', 10, 500],
        ['transfer_to_pharmacy', 20, 1000],
        ['write_off', 5, 250],
    ];

    foreach ($movements as $movement) {
        Transaction::factory()->create([
            'period_id' => 1,
            'supplier_item_id' => $supplierItem->id,
            'type' => $movement[0],
            'quantity' => $movement[1],
            'total_cost' => $movement[2],
            'transaction_date' => '2026-01-05',
            'profile_id' => $profile->id,
        ]);
    }

    // 100 + 5 - 30 - 10 - 20 - 5 = 40
    // 5000 + 250 - 1500 - 500 - 1000 - 250 = 2000
    $balance = (new BalanceService)->balanceFor($supplierItem->id, 1);

    expect($balance['quantity'])->toBe(40.0);
    expect($balance['total_cost'])->toBe(2000.0);
});

test('isolates balances by period', function () {
    $supplierItem = SupplierItem::factory()->create(['price' => 100, 'effective_date' => '2026-01-01']);
    $profile = Profile::factory()->create();

    Transaction::factory()->create([
        'period_id' => 1,
        'supplier_item_id' => $supplierItem->id,
        'type' => 'received',
        'quantity' => 10,
        'total_cost' => 1000,
        'transaction_date' => '2026-01-05',
        'profile_id' => $profile->id,
    ]);

    Transaction::factory()->create([
        'period_id' => 2,
        'supplier_item_id' => $supplierItem->id,
        'type' => 'received',
        'quantity' => 7,
        'total_cost' => 700,
        'transaction_date' => '2026-02-05',
        'profile_id' => $profile->id,
    ]);

    $service = new BalanceService;

    expect($service->balanceFor($supplierItem->id, 1)['quantity'])->toBe(10.0);
    expect($service->balanceFor($supplierItem->id, 2)['quantity'])->toBe(7.0);
});

test('reports negative balances for a period', function () {
    $supplierItem = SupplierItem::factory()->create(['price' => 100, 'effective_date' => '2026-01-01']);
    $profile = Profile::factory()->create();

    Transaction::factory()->create([
        'period_id' => 1,
        'supplier_item_id' => $supplierItem->id,
        'type' => 'consumption',
        'quantity' => 5,
        'total_cost' => 500,
        'transaction_date' => '2026-01-05',
        'profile_id' => $profile->id,
    ]);

    $negatives = (new BalanceService)->negativeBalances(1);

    expect($negatives)->toHaveKey($supplierItem->id);
    expect($negatives[$supplierItem->id]['quantity'])->toBe(-5.0);
});

test('signed quantity applies the correct sign per movement type', function () {
    $service = new BalanceService;

    expect($service->signedQuantity('received', 10.0))->toBe(10.0);
    expect($service->signedQuantity('return_from_ward', 10.0))->toBe(10.0);
    expect($service->signedQuantity('consumption', 10.0))->toBe(-10.0);
    expect($service->signedQuantity('return_to_supplier', 10.0))->toBe(-10.0);
    expect($service->signedQuantity('transfer_to_pharmacy', 10.0))->toBe(-10.0);
    expect($service->signedQuantity('write_off', 10.0))->toBe(-10.0);
});

test('projected balance adds an increasing movement to the current balance', function () {
    $supplierItem = SupplierItem::factory()->create(['price' => 100, 'effective_date' => '2026-01-01']);
    $profile = Profile::factory()->create();

    Transaction::factory()->create([
        'period_id' => 1,
        'supplier_item_id' => $supplierItem->id,
        'type' => 'received',
        'quantity' => 10,
        'total_cost' => 1000,
        'transaction_date' => '2026-01-05',
        'profile_id' => $profile->id,
    ]);

    $projected = (new BalanceService)->projectedBalance($supplierItem->id, 1, 'received', 5, 100);

    expect($projected['quantity'])->toBe(15.0);
    expect($projected['total_cost'])->toBe(1500.0);
});

test('projected balance subtracts a decreasing movement from the current balance', function () {
    $supplierItem = SupplierItem::factory()->create(['price' => 100, 'effective_date' => '2026-01-01']);
    $profile = Profile::factory()->create();

    Transaction::factory()->create([
        'period_id' => 1,
        'supplier_item_id' => $supplierItem->id,
        'type' => 'received',
        'quantity' => 10,
        'total_cost' => 1000,
        'transaction_date' => '2026-01-05',
        'profile_id' => $profile->id,
    ]);

    $projected = (new BalanceService)->projectedBalance($supplierItem->id, 1, 'consumption', 4, 100);

    expect($projected['quantity'])->toBe(6.0);
    expect($projected['total_cost'])->toBe(600.0);
});

test('a period begins from the previous period ending snapshot', function () {
    $supplierItem = SupplierItem::factory()->create(['price' => 100, 'effective_date' => '2026-01-01']);
    $profile = Profile::factory()->create();

    $january = Period::factory()->create(['year' => 2026, 'month' => 1]);
    $february = Period::factory()->create(['year' => 2026, 'month' => 2]);

    PeriodBalance::factory()->create([
        'period_id' => $january->id,
        'supplier_item_id' => $supplierItem->id,
        'beginning_quantity' => 0,
        'beginning_cost' => 0,
        'ending_quantity' => 7,
        'ending_cost' => 700,
    ]);

    Transaction::factory()->create([
        'period_id' => $february->id,
        'supplier_item_id' => $supplierItem->id,
        'type' => 'consumption',
        'quantity' => 2,
        'total_cost' => 200,
        'transaction_date' => '2026-02-05',
        'profile_id' => $profile->id,
    ]);

    $service = new BalanceService;

    expect($service->beginningBalanceFor($supplierItem->id, $february->id)['quantity'])->toBe(7.0);
    expect($service->balanceFor($supplierItem->id, $february->id)['quantity'])->toBe(5.0);
    expect($service->balanceFor($supplierItem->id, $february->id)['total_cost'])->toBe(500.0);
});

test('a period with no preceding snapshot begins at zero', function () {
    $supplierItem = SupplierItem::factory()->create(['price' => 100, 'effective_date' => '2026-01-01']);
    $period = Period::factory()->create(['year' => 2026, 'month' => 3]);

    $beginning = (new BalanceService)->beginningBalanceFor($supplierItem->id, $period->id);

    expect($beginning['quantity'])->toBe(0.0);
    expect($beginning['total_cost'])->toBe(0.0);
});

test('carried-forward balance counts toward the negative-balance check', function () {
    $supplierItem = SupplierItem::factory()->create(['price' => 100, 'effective_date' => '2026-01-01']);
    $profile = Profile::factory()->create();

    $january = Period::factory()->create(['year' => 2026, 'month' => 1]);
    $february = Period::factory()->create(['year' => 2026, 'month' => 2]);

    PeriodBalance::factory()->create([
        'period_id' => $january->id,
        'supplier_item_id' => $supplierItem->id,
        'ending_quantity' => 3,
        'ending_cost' => 300,
    ]);

    Transaction::factory()->create([
        'period_id' => $february->id,
        'supplier_item_id' => $supplierItem->id,
        'type' => 'consumption',
        'quantity' => 5,
        'total_cost' => 500,
        'transaction_date' => '2026-02-05',
        'profile_id' => $profile->id,
    ]);

    $negatives = (new BalanceService)->negativeBalances($february->id);

    expect($negatives)->toHaveKey($supplierItem->id);
    expect($negatives[$supplierItem->id]['quantity'])->toBe(-2.0);
});
