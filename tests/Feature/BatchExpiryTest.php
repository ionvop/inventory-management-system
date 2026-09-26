<?php

use App\Models\Batch;
use App\Models\Item;
use App\Models\Profile;
use App\Models\Supplier;
use App\Models\SupplierItem;
use Illuminate\Support\Carbon;

/**
 * Create a batch with an expiration date relative to today.
 */
function batchExpiringIn(int $days, array $attributes = []): Batch
{
    $supplier = Supplier::factory()->create();
    $item = Item::factory()->create();

    $supplierItem = SupplierItem::factory()->create([
        'supplier_id' => $supplier->id,
        'item_id' => $item->id,
        'price' => 100,
        'effective_date' => '2026-01-01',
        'active' => true,
    ]);

    return Batch::factory()->create(array_merge([
        'supplier_item_id' => $supplierItem->id,
        'expiration_date' => Carbon::today()->addDays($days)->toDateString(),
        'status' => Batch::STATUS_ACTIVE,
    ], $attributes));
}

test('a batch expiring within the threshold is near expiry', function () {
    $batch = batchExpiringIn(90);

    expect($batch->expiryStatus())->toBe(Batch::STATUS_NEAR_EXPIRY);
    expect($batch->daysUntilExpiry())->toBe(90);
});

test('a batch expiring beyond the threshold is active', function () {
    $batch = batchExpiringIn(91);

    expect($batch->expiryStatus())->toBe(Batch::STATUS_ACTIVE);
});

test('a batch expiring today is near expiry', function () {
    $batch = batchExpiringIn(0);

    expect($batch->expiryStatus())->toBe(Batch::STATUS_NEAR_EXPIRY);
});

test('a batch past its expiration date is expired', function () {
    $batch = batchExpiringIn(-1);

    expect($batch->expiryStatus())->toBe(Batch::STATUS_EXPIRED);
    expect($batch->daysUntilExpiry())->toBe(-1);
});

test('a damaged batch keeps its status regardless of the expiration date', function () {
    $batch = batchExpiringIn(365, ['status' => Batch::STATUS_DAMAGED]);

    expect($batch->expiryStatus())->toBe(Batch::STATUS_DAMAGED);
});

test('the near-expiry threshold is configurable', function () {
    config(['inventory.near_expiry_days' => 30]);

    expect(batchExpiringIn(40)->expiryStatus())->toBe(Batch::STATUS_ACTIVE);
    expect(batchExpiringIn(30)->expiryStatus())->toBe(Batch::STATUS_NEAR_EXPIRY);
});

test('the near-expiry and expired scopes select the right batches', function () {
    $nearExpiry = batchExpiringIn(30);
    $expired = batchExpiringIn(-5);
    $active = batchExpiringIn(200);
    $damaged = batchExpiringIn(-5, ['status' => Batch::STATUS_DAMAGED]);

    expect(Batch::query()->nearExpiry()->pluck('id')->all())->toBe([$nearExpiry->id]);
    expect(Batch::query()->expired()->pluck('id')->all())->toBe([$expired->id]);
    expect(Batch::query()->expiringWithin(60)->pluck('id')->all())->toBe([$nearExpiry->id]);
    expect($damaged->expiryStatus())->toBe(Batch::STATUS_DAMAGED);
});

test('any role can view the batch expiry dashboard', function () {
    $staff = Profile::factory()->create();
    $nearExpiry = batchExpiringIn(30);
    $expired = batchExpiringIn(-5);
    batchExpiringIn(200);

    $response = $this->withSession(['active_profile_id' => $staff->id])
        ->get(route('batches.index'));

    $response->assertOk();
    $response->assertInertia(function ($page) use ($nearExpiry, $expired) {
        $page->component('Batches');
        $page->has('batches', 3);
        $page->where('counts.expired', 1);
        $page->where('counts.near_expiry', 1);
        $page->where('counts.active', 1);
        $page->where('counts.damaged', 0);
        $page->where('nearExpiryDays', 90);
        // Ordered by expiration date ascending: expired first, then near expiry.
        $page->where('batches.0.id', $expired->id);
        $page->where('batches.0.status', Batch::STATUS_EXPIRED);
        $page->where('batches.1.id', $nearExpiry->id);
        $page->where('batches.1.status', Batch::STATUS_NEAR_EXPIRY);
    });
});

test('the batch expiry dashboard requires an active profile', function () {
    $this->get(route('batches.index'))->assertRedirect(route('profiles.index'));
});
