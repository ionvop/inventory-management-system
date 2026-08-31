<?php

use App\Models\Item;
use App\Models\Transaction;
use App\Models\User;

it('returns the inventory report as json by default', function () {
    actingAsUser();
    $item = Item::factory()->create(['name' => 'Sugar']);
    Transaction::factory()->create(['item_id' => $item->id, 'movement' => 'in', 'quantity' => 5]);

    $this->getJson('/api/reports/inventory')
        ->assertOk()
        ->assertJsonStructure([
            'data' => ['generated_time', 'date_from', 'date_to', 'items'],
        ])
        ->assertJsonPath('data.items.0.name', 'Sugar');
});

it('returns the inventory report as csv', function () {
    actingAsUser();
    $item = Item::factory()->create(['name' => 'Sugar', 'unit' => 'kg']);
    Transaction::factory()->create(['item_id' => $item->id, 'movement' => 'in', 'quantity' => 5]);

    $response = $this->get('/api/reports/inventory?format=csv');

    $response->assertOk();
    $content = $response->streamedContent();

    // fputcsv quotes fields depending on locale, so parse the CSV rather than
    // matching the exact rendered string.
    $rows = array_map('str_getcsv', array_filter(explode("\n", trim($content))));

    expect($rows[0])->toBe(['Item', 'Unit', 'Current Stock', 'Minimum Stock', 'Transaction Date', 'Movement', 'Quantity', 'User']);
    expect($rows[1][0])->toBe('Sugar')
        ->and($rows[1][1])->toBe('kg')
        ->and($rows[1][6])->toBe('5');
});

it('returns the inventory report as pdf', function () {
    actingAsUser();
    Item::factory()->create(['name' => 'Sugar']);

    $this->get('/api/reports/inventory?format=pdf')
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('filters the report by date range', function () {
    actingAsUser();
    $item = Item::factory()->create(['name' => 'Sugar']);
    Transaction::factory()->create(['item_id' => $item->id, 'movement' => 'in', 'quantity' => 5, 'posted_at' => now()->subDays(10)]);
    Transaction::factory()->create(['item_id' => $item->id, 'movement' => 'in', 'quantity' => 3, 'posted_at' => now()]);

    $this->getJson('/api/reports/inventory?date_from='.now()->subDays(2)->format('Y-m-d'))
        ->assertOk()
        ->assertJsonCount(1, 'data.items.0.transactions');
});