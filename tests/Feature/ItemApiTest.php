<?php

use App\Models\Item;
use App\Models\Transaction;
use App\Models\User;

// All item routes are behind the resolve.user middleware.

it('lists items with computed stock and pagination', function () {
    actingAsUser();
    Item::factory()->count(3)->create();

    $this->getJson('/api/items')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [['id', 'name', 'unit', 'minimum_stock', 'current_stock', 'is_low_stock']],
            'pagination' => ['page', 'limit', 'total', 'total_pages'],
        ])
        ->assertJsonPath('pagination.total', 3);
});

it('computes current stock from transactions', function () {
    actingAsUser();
    $item = Item::factory()->create(['minimum_stock' => 0]);
    Transaction::factory()->create(['item_id' => $item->id, 'movement' => 'in', 'quantity' => 10]);
    Transaction::factory()->create(['item_id' => $item->id, 'movement' => 'out', 'quantity' => 4]);

    $this->getJson('/api/items')
        ->assertOk()
        ->assertJsonPath('data.0.current_stock', 6)
        ->assertJsonPath('data.0.is_low_stock', false);
});

it('filters items by search term', function () {
    actingAsUser();
    Item::factory()->create(['name' => 'Sugar']);
    Item::factory()->create(['name' => 'Salt']);

    $this->getJson('/api/items?search=Sug')
        ->assertOk()
        ->assertJsonPath('pagination.total', 1)
        ->assertJsonPath('data.0.name', 'Sugar');
});

it('filters items to low stock only', function () {
    actingAsUser();
    $low = Item::factory()->create(['name' => 'Low', 'minimum_stock' => 10]);
    $ok = Item::factory()->create(['name' => 'Ok', 'minimum_stock' => 0]);
    Transaction::factory()->create(['item_id' => $low->id, 'movement' => 'in', 'quantity' => 5]);
    Transaction::factory()->create(['item_id' => $ok->id, 'movement' => 'in', 'quantity' => 20]);

    $this->getJson('/api/items?low_stock=true')
        ->assertOk()
        ->assertJsonPath('pagination.total', 1)
        ->assertJsonPath('data.0.name', 'Low');
});

it('sorts items by name descending', function () {
    actingAsUser();
    Item::factory()->create(['name' => 'Alpha']);
    Item::factory()->create(['name' => 'Beta']);

    $this->getJson('/api/items?sort=name&order=desc')
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Beta');
});

it('paginates items manually', function () {
    actingAsUser();
    Item::factory()->count(5)->create();

    $this->getJson('/api/items?page=2&limit=2')
        ->assertOk()
        ->assertJsonPath('pagination.page', 2)
        ->assertJsonPath('pagination.limit', 2)
        ->assertJsonPath('pagination.total', 5)
        ->assertJsonPath('pagination.total_pages', 3)
        ->assertJsonCount(2, 'data');
});

it('creates an item', function () {
    actingAsUser();

    $this->postJson('/api/items', ['name' => 'Sugar', 'unit' => 'kg', 'minimum_stock' => 10])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Sugar')
        ->assertJsonPath('data.current_stock', 0);

    $this->assertDatabaseHas('items', ['name' => 'Sugar']);
});

it('rejects creating an item with a duplicate name', function () {
    actingAsUser();
    Item::factory()->create(['name' => 'Sugar']);

    $this->postJson('/api/items', ['name' => 'Sugar', 'unit' => 'kg'])
        ->assertStatus(400)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');
});

it('rejects creating an item with a negative minimum stock', function () {
    actingAsUser();

    $this->postJson('/api/items', ['name' => 'Sugar', 'unit' => 'kg', 'minimum_stock' => -1])
        ->assertStatus(400)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');
});

it('shows an item with computed stock', function () {
    actingAsUser();
    $item = Item::factory()->create(['minimum_stock' => 0]);
    Transaction::factory()->create(['item_id' => $item->id, 'movement' => 'in', 'quantity' => 7]);

    $this->getJson("/api/items/{$item->id}")
        ->assertOk()
        ->assertJsonPath('data.current_stock', 7);
});

it('returns 404 for a missing item', function () {
    actingAsUser();

    $this->getJson('/api/items/9999')
        ->assertStatus(404)
        ->assertJsonPath('error.code', 'NOT_FOUND');
});

it('updates an item', function () {
    actingAsUser();
    $item = Item::factory()->create(['name' => 'Sugar', 'unit' => 'kg']);

    $this->putJson("/api/items/{$item->id}", ['name' => 'Brown Sugar'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Brown Sugar');

    $this->assertDatabaseHas('items', ['name' => 'Brown Sugar']);
});

it('deletes an item and cascades its transactions', function () {
    actingAsUser();
    $item = Item::factory()->create();
    Transaction::factory()->create(['item_id' => $item->id]);

    $this->deleteJson("/api/items/{$item->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('items', ['id' => $item->id]);
    $this->assertDatabaseMissing('transactions', ['item_id' => $item->id]);
});

it('returns low stock items via the dedicated endpoint', function () {
    actingAsUser();
    $low = Item::factory()->create(['name' => 'Low', 'minimum_stock' => 10]);
    Transaction::factory()->create(['item_id' => $low->id, 'movement' => 'in', 'quantity' => 2]);

    $this->getJson('/api/items/low-stock')
        ->assertOk()
        ->assertJsonPath('pagination.total', 1)
        ->assertJsonPath('data.0.name', 'Low');
});