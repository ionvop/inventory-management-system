<?php

use App\Models\AuditLog;
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
function periodSupplierItem(array $attributes = []): SupplierItem
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
function recordMovement(Period $period, SupplierItem $supplierItem, Profile $profile, string $type, float $quantity, float $totalCost): Transaction
{
    return Transaction::factory()->create([
        'period_id' => $period->id,
        'supplier_item_id' => $supplierItem->id,
        'type' => $type,
        'quantity' => $quantity,
        'unit_cost' => 100,
        'total_cost' => $totalCost,
        'transaction_date' => sprintf('%04d-%02d-05', $period->year, $period->month),
        'profile_id' => $profile->id,
    ]);
}

test('a supervisor can view the periods screen', function () {
    $supervisor = Profile::factory()->supervisor()->create();
    $period = Period::factory()->create(['year' => 2026, 'month' => 1]);

    $response = $this->withSession(['active_profile_id' => $supervisor->id])
        ->get(route('periods.index'));

    $response->assertOk();
    $response->assertInertia(function ($page) use ($period) {
        $page->component('Periods');
        $page->has('periods', 1);
        $page->where('periods.0.id', $period->id);
        $page->where('periods.0.status', 'open');
        $page->where('canReopen', false);
    });
});

test('a staff profile cannot view the periods screen', function () {
    $staff = Profile::factory()->create();

    $this->withSession(['active_profile_id' => $staff->id])
        ->get(route('periods.index'))
        ->assertForbidden();
});

test('closing a period snapshots each item ending balance', function () {
    $supervisor = Profile::factory()->supervisor()->create();
    $period = Period::factory()->create(['year' => 2026, 'month' => 1]);
    $supplierItem = periodSupplierItem();

    recordMovement($period, $supplierItem, $supervisor, 'received', 10, 1000);
    recordMovement($period, $supplierItem, $supervisor, 'consumption', 3, 300);

    $this->withSession(['active_profile_id' => $supervisor->id])
        ->post(route('periods.close', $period->id))
        ->assertRedirectBack();

    $period->refresh();
    expect($period->status)->toBe('closed');
    expect($period->closed_by)->toBe($supervisor->id);
    expect($period->closed_at)->not->toBeNull();

    $snapshot = PeriodBalance::query()->sole();
    expect($snapshot->period_id)->toBe($period->id);
    expect($snapshot->supplier_item_id)->toBe($supplierItem->id);
    expect((float) $snapshot->beginning_quantity)->toBe(0.0);
    expect((float) $snapshot->ending_quantity)->toBe(7.0);
    expect((float) $snapshot->ending_cost)->toBe(700.0);
});

test('closing a period is blocked while an item has a negative balance', function () {
    $supervisor = Profile::factory()->supervisor()->create();
    $period = Period::factory()->create(['year' => 2026, 'month' => 1]);
    $supplierItem = periodSupplierItem();

    recordMovement($period, $supplierItem, $supervisor, 'consumption', 5, 500);

    $this->withSession(['active_profile_id' => $supervisor->id])
        ->post(route('periods.close', $period->id))
        ->assertSessionHasErrors('period');

    expect($period->refresh()->status)->toBe('open');
    expect(PeriodBalance::query()->count())->toBe(0);
});

test('closing an already closed period is rejected', function () {
    $supervisor = Profile::factory()->supervisor()->create();
    $period = Period::factory()->create([
        'year' => 2026,
        'month' => 1,
        'status' => 'closed',
        'closed_at' => now(),
        'closed_by' => $supervisor->id,
    ]);

    $this->withSession(['active_profile_id' => $supervisor->id])
        ->post(route('periods.close', $period->id))
        ->assertSessionHasErrors('period');
});

test('closing a period writes an audit log', function () {
    $supervisor = Profile::factory()->supervisor()->create();
    $period = Period::factory()->create(['year' => 2026, 'month' => 1]);

    $this->withSession(['active_profile_id' => $supervisor->id])
        ->post(route('periods.close', $period->id));

    $log = AuditLog::query()->sole();

    expect($log->profile_id)->toBe($supervisor->id);
    expect($log->action)->toBe('close');
    expect($log->auditable_type)->toBe(Period::class);
    expect($log->after['status'])->toBe('closed');
});

test('an administrator can reopen a closed period with a reason', function () {
    $admin = Profile::factory()->administrator()->create();
    $period = Period::factory()->create([
        'year' => 2026,
        'month' => 1,
        'status' => 'closed',
        'closed_at' => now(),
        'closed_by' => $admin->id,
    ]);

    $this->withSession(['active_profile_id' => $admin->id])
        ->post(route('periods.reopen', $period->id), [
            'reopened_reason' => 'Correcting a mis-keyed receipt.',
        ])
        ->assertRedirectBack();

    $period->refresh();
    expect($period->status)->toBe('open');
    expect($period->closed_at)->toBeNull();
    expect($period->closed_by)->toBeNull();
    expect($period->reopened_reason)->toBe('Correcting a mis-keyed receipt.');

    $log = AuditLog::query()->sole();
    expect($log->action)->toBe('reopen');
    expect($log->before['status'])->toBe('closed');
    expect($log->after['status'])->toBe('open');
});

test('reopening requires a reason', function () {
    $admin = Profile::factory()->administrator()->create();
    $period = Period::factory()->create([
        'year' => 2026,
        'month' => 1,
        'status' => 'closed',
        'closed_at' => now(),
        'closed_by' => $admin->id,
    ]);

    $this->withSession(['active_profile_id' => $admin->id])
        ->post(route('periods.reopen', $period->id), ['reopened_reason' => ''])
        ->assertSessionHasErrors('reopened_reason');

    expect($period->refresh()->status)->toBe('closed');
});

test('a supervisor cannot reopen a closed period', function () {
    $supervisor = Profile::factory()->supervisor()->create();
    $period = Period::factory()->create([
        'year' => 2026,
        'month' => 1,
        'status' => 'closed',
        'closed_at' => now(),
        'closed_by' => $supervisor->id,
    ]);

    $this->withSession(['active_profile_id' => $supervisor->id])
        ->post(route('periods.reopen', $period->id), [
            'reopened_reason' => 'Trying anyway.',
        ])
        ->assertForbidden();

    expect($period->refresh()->status)->toBe('closed');
});
