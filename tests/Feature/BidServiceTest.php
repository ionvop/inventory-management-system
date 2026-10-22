<?php

use App\Models\Bid;
use App\Models\Item;
use App\Models\Supplier;
use App\Models\SupplierItem;
use App\Models\Transaction;
use App\Services\BidService;

/**
 * Create a supplier/item pair with an active contract price.
 *
 * @return array{0: Supplier, 1: Item, 2: SupplierItem}
 */
function bidPair(array $attributes = []): array
{
    $supplier = Supplier::factory()->create();
    $item = Item::factory()->create();

    $supplierItem = SupplierItem::factory()->create(array_merge([
        'supplier_id' => $supplier->id,
        'item_id' => $item->id,
        'price' => 100,
        'effective_date' => '2026-01-01',
        'active' => true,
    ], $attributes));

    return [$supplier, $item, $supplierItem];
}

/**
 * Record a movement against a supplier item.
 */
function bidMovement(SupplierItem $supplierItem, string $type, float $quantity, string $date, array $attributes = []): Transaction
{
    return Transaction::factory()->create(array_merge([
        'supplier_item_id' => $supplierItem->id,
        'type' => $type,
        'quantity' => $quantity,
        'unit_cost' => 100,
        'total_cost' => $quantity * 100,
        'transaction_date' => $date,
    ], $attributes));
}

test('receivedSince counts only receipts on or after the set date', function () {
    [$supplier, $item, $supplierItem] = bidPair();

    $bid = Bid::factory()->create([
        'supplier_id' => $supplier->id,
        'item_id' => $item->id,
        'bid_quantity' => 10000,
        'set_date' => '2026-02-01',
    ]);

    bidMovement($supplierItem, 'received', 500, '2026-01-15'); // before the bid
    bidMovement($supplierItem, 'received', 2000, '2026-02-10'); // after the bid
    bidMovement($supplierItem, 'received', 3000, '2026-03-01'); // after the bid

    expect(app(BidService::class)->receivedSince($bid))->toBe(5000.0);
});

test('receivedSince ignores non-received movements', function () {
    [$supplier, $item, $supplierItem] = bidPair();

    $bid = Bid::factory()->create([
        'supplier_id' => $supplier->id,
        'item_id' => $item->id,
        'bid_quantity' => 10000,
        'set_date' => '2026-02-01',
    ]);

    bidMovement($supplierItem, 'received', 2000, '2026-02-10');
    bidMovement($supplierItem, 'consumption', 500, '2026-02-11');
    bidMovement($supplierItem, 'return_to_supplier', 300, '2026-02-12');
    bidMovement($supplierItem, 'write_off', 100, '2026-02-13');

    expect(app(BidService::class)->receivedSince($bid))->toBe(2000.0);
});

test('receivedSince aggregates receipts across every price row for the pair', function () {
    [$supplier, $item, $firstPrice] = bidPair();

    // A price change creates a new supplier_items row for the same pair.
    $secondPrice = SupplierItem::factory()->create([
        'supplier_id' => $supplier->id,
        'item_id' => $item->id,
        'price' => 125,
        'effective_date' => '2026-03-01',
        'active' => true,
    ]);

    $bid = Bid::factory()->create([
        'supplier_id' => $supplier->id,
        'item_id' => $item->id,
        'bid_quantity' => 10000,
        'set_date' => '2026-02-01',
    ]);

    bidMovement($firstPrice, 'received', 2000, '2026-02-10');
    bidMovement($secondPrice, 'received', 3000, '2026-03-10');

    expect(app(BidService::class)->receivedSince($bid))->toBe(5000.0);
});

test('a reversal of a receipt adds the quantity back to the bid', function () {
    [$supplier, $item, $supplierItem] = bidPair();

    $bid = Bid::factory()->create([
        'supplier_id' => $supplier->id,
        'item_id' => $item->id,
        'bid_quantity' => 10000,
        'set_date' => '2026-02-01',
    ]);

    $receipt = bidMovement($supplierItem, 'received', 2000, '2026-02-10');

    // A reversal is dated today but cancels the receipt it references.
    bidMovement($supplierItem, 'received', 2000, now()->toDateString(), [
        'reverses_transaction_id' => $receipt->id,
    ]);

    expect(app(BidService::class)->receivedSince($bid))->toBe(0.0);
});

test('a reversal of a pre-bid receipt does not credit the bid', function () {
    [$supplier, $item, $supplierItem] = bidPair();

    $bid = Bid::factory()->create([
        'supplier_id' => $supplier->id,
        'item_id' => $item->id,
        'bid_quantity' => 10000,
        'set_date' => '2026-02-01',
    ]);

    $receipt = bidMovement($supplierItem, 'received', 2000, '2026-01-15');

    // The reversal is dated today, but it cancels a receipt that predates the
    // bid, so it must not credit the bid.
    bidMovement($supplierItem, 'received', 2000, now()->toDateString(), [
        'reverses_transaction_id' => $receipt->id,
    ]);

    expect(app(BidService::class)->receivedSince($bid))->toBe(0.0);
});

test('remaining is the bid quantity less everything received since it was set', function () {
    [$supplier, $item, $supplierItem] = bidPair();

    $bid = Bid::factory()->create([
        'supplier_id' => $supplier->id,
        'item_id' => $item->id,
        'bid_quantity' => 10000,
        'set_date' => '2026-02-01',
    ]);

    bidMovement($supplierItem, 'received', 9200, '2026-02-10');

    expect(app(BidService::class)->remaining($bid))->toBe(800.0);
});

test('remaining can go negative when more is received than was bid', function () {
    [$supplier, $item, $supplierItem] = bidPair();

    $bid = Bid::factory()->create([
        'supplier_id' => $supplier->id,
        'item_id' => $item->id,
        'bid_quantity' => 1000,
        'set_date' => '2026-02-01',
    ]);

    bidMovement($supplierItem, 'received', 1500, '2026-02-10');

    expect(app(BidService::class)->remaining($bid))->toBe(-500.0);
});

test('needsReorder is true at or below the configured threshold', function () {
    config(['inventory.bid_reorder_threshold' => 1000]);

    [$supplier, $item, $supplierItem] = bidPair();

    $bid = Bid::factory()->create([
        'supplier_id' => $supplier->id,
        'item_id' => $item->id,
        'bid_quantity' => 10000,
        'set_date' => '2026-02-01',
    ]);

    bidMovement($supplierItem, 'received', 9000, '2026-02-10');

    // Remaining is exactly 1000, which is at the threshold.
    expect(app(BidService::class)->needsReorder($bid))->toBeTrue();
});

test('needsReorder is false when the remaining quantity is above the threshold', function () {
    config(['inventory.bid_reorder_threshold' => 1000]);

    [$supplier, $item, $supplierItem] = bidPair();

    $bid = Bid::factory()->create([
        'supplier_id' => $supplier->id,
        'item_id' => $item->id,
        'bid_quantity' => 10000,
        'set_date' => '2026-02-01',
    ]);

    bidMovement($supplierItem, 'received', 8000, '2026-02-10');

    expect(app(BidService::class)->needsReorder($bid))->toBeFalse();
});

test('currentFor resolves the most recently set bid for a pair', function () {
    [$supplier, $item] = bidPair();

    Bid::factory()->create([
        'supplier_id' => $supplier->id,
        'item_id' => $item->id,
        'bid_quantity' => 10000,
        'set_date' => '2026-01-01',
    ]);
    $latest = Bid::factory()->create([
        'supplier_id' => $supplier->id,
        'item_id' => $item->id,
        'bid_quantity' => 12000,
        'set_date' => '2026-07-01',
    ]);

    $current = Bid::currentFor($supplier->id, $item->id);

    expect($current)->not->toBeNull();
    expect($current->id)->toBe($latest->id);
    expect($current->bid_quantity)->toBe('12000.00');
});

test('currentFor returns null when a pair has never been bid on', function () {
    [$supplier, $item] = bidPair();

    expect(Bid::currentFor($supplier->id, $item->id))->toBeNull();
});

test('rows lists every transactable pair including those without a bid', function () {
    [$supplier, $item, $supplierItem] = bidPair();

    Bid::factory()->create([
        'supplier_id' => $supplier->id,
        'item_id' => $item->id,
        'bid_quantity' => 10000,
        'set_date' => '2026-02-01',
    ]);

    bidMovement($supplierItem, 'received', 9200, '2026-02-10');

    // A second pair with no bid at all.
    bidPair();

    $rows = app(BidService::class)->rows();

    expect($rows)->toHaveCount(2);

    $withBid = collect($rows)->firstWhere('has_bid', true);
    expect($withBid['remaining'])->toBe(800.0);
    expect($withBid['received_since'])->toBe(9200.0);

    $withoutBid = collect($rows)->firstWhere('has_bid', false);
    expect($withoutBid['remaining'])->toBeNull();
    expect($withoutBid['bid_quantity'])->toBeNull();
});

test('reorderCount counts only current bids at or below the threshold', function () {
    config(['inventory.bid_reorder_threshold' => 1000]);

    [$supplier, $item, $supplierItem] = bidPair();

    Bid::factory()->create([
        'supplier_id' => $supplier->id,
        'item_id' => $item->id,
        'bid_quantity' => 10000,
        'set_date' => '2026-02-01',
    ]);

    bidMovement($supplierItem, 'received', 9500, '2026-02-10');

    // A second pair with no bid must not be counted.
    bidPair();

    expect(app(BidService::class)->reorderCount())->toBe(1);
});
