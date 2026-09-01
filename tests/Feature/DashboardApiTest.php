<?php

use App\Models\Item;
use App\Models\Transaction;
use App\Models\User;

it('returns the dashboard summary shape', function () {
    actingAsUser();

    $this->getJson('/api/dashboard/summary')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'total_items',
                'low_stock_count',
                'low_stock_items',
                'today_transactions' => ['in_count', 'out_count'],
                'recent_transactions',
            ],
        ]);
});

it('counts total and low stock items', function () {
    actingAsUser();
    $low = Item::factory()->create(['name' => 'Low', 'minimum_stock' => 10]);
    $ok = Item::factory()->create(['name' => 'Ok', 'minimum_stock' => 0]);
    Transaction::factory()->create(['item_id' => $low->id, 'movement' => 'in', 'quantity' => 2]);
    Transaction::factory()->create(['item_id' => $ok->id, 'movement' => 'in', 'quantity' => 20]);

    $this->getJson('/api/dashboard/summary')
        ->assertOk()
        ->assertJsonPath('data.total_items', 2)
        ->assertJsonPath('data.low_stock_count', 1)
        ->assertJsonPath('data.low_stock_items.0.name', 'Low');
});

it('counts today transactions by movement', function () {
    actingAsUser();
    $item = Item::factory()->create();
    Transaction::factory()->create(['item_id' => $item->id, 'movement' => 'in', 'posted_at' => now()]);
    Transaction::factory()->create(['item_id' => $item->id, 'movement' => 'in', 'posted_at' => now()]);
    Transaction::factory()->create(['item_id' => $item->id, 'movement' => 'out', 'posted_at' => now()]);
    // Outside today's window.
    Transaction::factory()->create(['item_id' => $item->id, 'movement' => 'in', 'posted_at' => now()->subDays(3)]);

    $this->getJson('/api/dashboard/summary')
        ->assertOk()
        ->assertJsonPath('data.today_transactions.in_count', 2)
        ->assertJsonPath('data.today_transactions.out_count', 1);
});

it('includes recent transactions', function () {
    actingAsUser();
    $item = Item::factory()->create();
    Transaction::factory()->create(['item_id' => $item->id, 'movement' => 'in', 'quantity' => 5]);

    $this->getJson('/api/dashboard/summary')
        ->assertOk()
        ->assertJsonCount(1, 'data.recent_transactions')
        ->assertJsonPath('data.recent_transactions.0.quantity', 5);
});

it('uses the X-Timezone header to determine today boundaries', function () {
    actingAsUser();
    $item = Item::factory()->create();
    // 01:00 UTC today == 21:00 yesterday in America/New_York (EDT, UTC-4).
    Transaction::factory()->create([
        'item_id' => $item->id,
        'movement' => 'in',
        'posted_at' => now()->startOfDay()->addHours(1),
    ]);

    // With UTC, this transaction is today.
    $this->getJson('/api/dashboard/summary')
        ->assertOk()
        ->assertJsonPath('data.today_transactions.in_count', 1);

    // With America/New_York, it falls on the previous day.
    $this->withHeader('X-Timezone', 'America/New_York')
        ->getJson('/api/dashboard/summary')
        ->assertOk()
        ->assertJsonPath('data.today_transactions.in_count', 0);
});