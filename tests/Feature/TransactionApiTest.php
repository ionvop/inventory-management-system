<?php

use App\Models\Item;
use App\Models\Transaction;
use App\Models\User;

it('lists transactions with filters and pagination', function () {
    actingAsUser();
    Transaction::factory()->count(3)->create();

    $this->getJson('/api/transactions')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [['id', 'item_id', 'user_id', 'movement', 'quantity', 'posted_at']],
            'pagination' => ['page', 'limit', 'total', 'total_pages'],
        ])
        ->assertJsonPath('pagination.total', 3);
});

it('filters transactions by movement', function () {
    actingAsUser();
    Transaction::factory()->create(['movement' => 'in']);
    Transaction::factory()->create(['movement' => 'out']);

    $this->getJson('/api/transactions?movement=in')
        ->assertOk()
        ->assertJsonPath('pagination.total', 1)
        ->assertJsonPath('data.0.movement', 'in');
});

it('filters transactions by item', function () {
    actingAsUser();
    $item = Item::factory()->create();
    Transaction::factory()->create(['item_id' => $item->id]);
    Transaction::factory()->create();

    $this->getJson('/api/transactions?item_id='.$item->id)
        ->assertOk()
        ->assertJsonPath('pagination.total', 1);
});

it('filters transactions by user', function () {
    actingAsUser();
    $user = User::factory()->create();
    Transaction::factory()->create(['user_id' => $user->id]);
    Transaction::factory()->create();

    $this->getJson('/api/transactions?user_id='.$user->id)
        ->assertOk()
        ->assertJsonPath('pagination.total', 1);
});

it('filters transactions by date range', function () {
    actingAsUser();
    Transaction::factory()->create(['posted_at' => now()->subDays(10)]);
    Transaction::factory()->create(['posted_at' => now()]);

    $this->getJson('/api/transactions?date_from='.now()->subDays(2)->format('Y-m-d'))
        ->assertOk()
        ->assertJsonPath('pagination.total', 1);
});

it('lists transactions for a specific item', function () {
    actingAsUser();
    $item = Item::factory()->create();
    Transaction::factory()->create(['item_id' => $item->id]);
    Transaction::factory()->create();

    $this->getJson("/api/items/{$item->id}/transactions")
        ->assertOk()
        ->assertJsonPath('pagination.total', 1);
});

it('reports the resulting stock after each transaction', function () {
    actingAsUser();
    $item = Item::factory()->create();
    $other = Item::factory()->create();

    // Item history: in 10, out 4, in 3 -> running balance 10, 6, 9.
    $in1 = Transaction::factory()->create(['item_id' => $item->id, 'movement' => 'in', 'quantity' => 10, 'posted_at' => now()->subDays(3)]);
    $out = Transaction::factory()->create(['item_id' => $item->id, 'movement' => 'out', 'quantity' => 4, 'posted_at' => now()->subDays(2)]);
    $in2 = Transaction::factory()->create(['item_id' => $item->id, 'movement' => 'in', 'quantity' => 3, 'posted_at' => now()->subDays(1)]);
    // Unrelated item must not affect the balance.
    Transaction::factory()->create(['item_id' => $other->id, 'movement' => 'in', 'quantity' => 99]);

    $this->getJson('/api/transactions?item_id='.$item->id.'&sort=posted_at&order=asc')
        ->assertOk()
        ->assertJsonPath('data.0.id', $in1->id)
        ->assertJsonPath('data.0.stock_after', 10)
        ->assertJsonPath('data.1.id', $out->id)
        ->assertJsonPath('data.1.stock_after', 6)
        ->assertJsonPath('data.2.id', $in2->id)
        ->assertJsonPath('data.2.stock_after', 9);
});

it('creates an in transaction and assigns the current user', function () {
    $user = actingAsUser();
    $item = Item::factory()->create();

    $this->postJson('/api/transactions', [
        'item_id' => $item->id,
        'movement' => 'in',
        'quantity' => 5,
    ])->assertCreated()
        ->assertJsonPath('data.movement', 'in')
        ->assertJsonPath('data.quantity', 5)
        ->assertJsonPath('data.user_id', $user->id);

    $this->assertDatabaseHas('transactions', [
        'item_id' => $item->id,
        'user_id' => $user->id,
        'movement' => 'in',
        'quantity' => 5,
    ]);
});

it('creates an out transaction when stock is sufficient', function () {
    actingAsUser();
    $item = Item::factory()->create();
    Transaction::factory()->create(['item_id' => $item->id, 'movement' => 'in', 'quantity' => 10]);

    $this->postJson('/api/transactions', [
        'item_id' => $item->id,
        'movement' => 'out',
        'quantity' => 4,
    ])->assertCreated()
        ->assertJsonPath('data.movement', 'out');
});

it('rejects an out transaction that exceeds stock', function () {
    actingAsUser();
    $item = Item::factory()->create();
    Transaction::factory()->create(['item_id' => $item->id, 'movement' => 'in', 'quantity' => 3]);

    $this->postJson('/api/transactions', [
        'item_id' => $item->id,
        'movement' => 'out',
        'quantity' => 10,
    ])->assertStatus(422)
        ->assertJsonPath('error.code', 'INSUFFICIENT_STOCK')
        ->assertJsonPath('error.details.current_stock', 3)
        ->assertJsonPath('error.details.requested', 10);
});

it('rejects a transaction with an invalid movement', function () {
    actingAsUser();
    $item = Item::factory()->create();

    $this->postJson('/api/transactions', [
        'item_id' => $item->id,
        'movement' => 'sideways',
        'quantity' => 1,
    ])->assertStatus(400)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');
});

it('rejects a transaction with a zero quantity', function () {
    actingAsUser();
    $item = Item::factory()->create();

    $this->postJson('/api/transactions', [
        'item_id' => $item->id,
        'movement' => 'in',
        'quantity' => 0,
    ])->assertStatus(400)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');
});

it('shows a transaction with its relations', function () {
    actingAsUser();
    $transaction = Transaction::factory()->create();

    $this->getJson("/api/transactions/{$transaction->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $transaction->id)
        ->assertJsonStructure(['data' => ['item' => ['id', 'name'], 'user' => ['id', 'username']]]);
});

it('updates a transaction', function () {
    actingAsUser();
    $transaction = Transaction::factory()->create(['movement' => 'in', 'quantity' => 2]);

    $this->putJson("/api/transactions/{$transaction->id}", ['quantity' => 9])
        ->assertOk()
        ->assertJsonPath('data.quantity', 9);
});

it('backs out the transaction effect when checking stock on update', function () {
    actingAsUser();
    $item = Item::factory()->create();
    // 10 in, 6 out -> current stock 4. The 6-out transaction is the one being edited.
    Transaction::factory()->create(['item_id' => $item->id, 'movement' => 'in', 'quantity' => 10]);
    $out = Transaction::factory()->create(['item_id' => $item->id, 'movement' => 'out', 'quantity' => 6]);

    // Excluding this transaction, stock is 10, so an out of 8 is allowed.
    $this->putJson("/api/transactions/{$out->id}", ['quantity' => 8])
        ->assertOk()
        ->assertJsonPath('data.quantity', 8);
});

it('rejects an update that would drive stock negative', function () {
    actingAsUser();
    $item = Item::factory()->create();
    Transaction::factory()->create(['item_id' => $item->id, 'movement' => 'in', 'quantity' => 10]);
    $out = Transaction::factory()->create(['item_id' => $item->id, 'movement' => 'out', 'quantity' => 6]);

    // Excluding this transaction, stock is 10, so an out of 12 would drive it negative.
    $this->putJson("/api/transactions/{$out->id}", ['quantity' => 12])
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'INSUFFICIENT_STOCK');
});

it('deletes a transaction', function () {
    actingAsUser();
    $transaction = Transaction::factory()->create();

    $this->deleteJson("/api/transactions/{$transaction->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('transactions', ['id' => $transaction->id]);
});