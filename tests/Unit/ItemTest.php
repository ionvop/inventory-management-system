<?php

use App\Models\Item;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('computes current stock as stock in minus stock out', function () {
    $item = Item::factory()->create();
    Transaction::factory()->create(['item_id' => $item->id, 'movement' => 'in', 'quantity' => 10]);
    Transaction::factory()->create(['item_id' => $item->id, 'movement' => 'in', 'quantity' => 5]);
    Transaction::factory()->create(['item_id' => $item->id, 'movement' => 'out', 'quantity' => 4]);

    $item->loadStock();

    expect($item->current_stock)->toBe(11);
});

it('returns zero current stock when there are no transactions', function () {
    $item = Item::factory()->create();

    $item->loadStock();

    expect($item->current_stock)->toBe(0);
});

it('flags an item as low stock when current stock is below minimum', function () {
    $item = Item::factory()->create(['minimum_stock' => 10]);
    Transaction::factory()->create(['item_id' => $item->id, 'movement' => 'in', 'quantity' => 5]);

    $item->loadStock();

    expect($item->is_low_stock)->toBeTrue();
});

it('does not flag an item as low stock when current stock equals minimum', function () {
    $item = Item::factory()->create(['minimum_stock' => 10]);
    Transaction::factory()->create(['item_id' => $item->id, 'movement' => 'in', 'quantity' => 10]);

    $item->loadStock();

    expect($item->is_low_stock)->toBeFalse();
});

it('computes stock via the withStock scope', function () {
    $item = Item::factory()->create(['minimum_stock' => 0]);
    Transaction::factory()->create(['item_id' => $item->id, 'movement' => 'in', 'quantity' => 7]);

    $loaded = Item::withStock()->find($item->id);

    expect($loaded->current_stock)->toBe(7);
});

it('has many transactions', function () {
    $item = Item::factory()->create();
    Transaction::factory()->count(3)->create(['item_id' => $item->id]);

    expect($item->transactions)->toHaveCount(3);
});