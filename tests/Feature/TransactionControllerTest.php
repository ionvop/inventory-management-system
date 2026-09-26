<?php

use App\Models\AuditLog;
use App\Models\Item;
use App\Models\Period;
use App\Models\Profile;
use App\Models\Supplier;
use App\Models\SupplierItem;
use App\Models\Transaction;
use App\Models\Ward;

/**
 * Create an active supplier item with a contract price in effect.
 */
function activeSupplierItem(array $attributes = []): SupplierItem
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

test('a staff profile can view the transaction screen', function () {
    $staff = Profile::factory()->create();
    $supplierItem = activeSupplierItem();

    $response = $this->withSession(['active_profile_id' => $staff->id])
        ->get(route('transactions.index'));

    $response->assertOk();
    $response->assertInertia(function ($page) use ($supplierItem) {
        $page->component('Transactions');
        $page->has('supplierItems', 1);
        $page->where('supplierItems.0.id', $supplierItem->id);
        $page->where('supplierItems.0.balance.quantity', 0);
        $page->where('canOverride', false);
    });
});

test('an administrator is flagged as able to override', function () {
    $admin = Profile::factory()->administrator()->create();

    $this->withSession(['active_profile_id' => $admin->id])
        ->get(route('transactions.index'))
        ->assertInertia(fn ($page) => $page->where('canOverride', true));
});

test('recording a received movement creates a batch and snapshots the price', function () {
    $staff = Profile::factory()->create();
    $supplierItem = activeSupplierItem(['price' => 250.50]);

    $response = $this->withSession(['active_profile_id' => $staff->id])
        ->post(route('transactions.store'), [
            'supplier_item_id' => $supplierItem->id,
            'type' => 'received',
            'quantity' => 10,
            'transaction_date' => '2026-01-05',
            'batch_number' => 'BATCH-001',
            'expiration_date' => '2026-12-31',
        ]);

    $response->assertRedirectBack();

    $this->assertDatabaseHas('transactions', [
        'supplier_item_id' => $supplierItem->id,
        'type' => 'received',
        'quantity' => 10,
        'unit_cost' => 250.50,
        'total_cost' => 2505.00,
        'profile_id' => $staff->id,
    ]);

    $this->assertDatabaseHas('batches', [
        'supplier_item_id' => $supplierItem->id,
        'batch_number' => 'BATCH-001',
        'expiration_date' => '2026-12-31',
    ]);

    $transaction = Transaction::query()->sole();
    expect($transaction->batch_id)->not->toBeNull();
});

test('a received movement requires a batch number and expiration date', function () {
    $staff = Profile::factory()->create();
    $supplierItem = activeSupplierItem();

    $response = $this->withSession(['active_profile_id' => $staff->id])
        ->post(route('transactions.store'), [
            'supplier_item_id' => $supplierItem->id,
            'type' => 'received',
            'quantity' => 5,
            'transaction_date' => '2026-01-05',
        ]);

    $response->assertSessionHasErrors(['batch_number', 'expiration_date']);
    $this->assertDatabaseCount('transactions', 0);
});

test('a return from ward requires a ward', function () {
    $staff = Profile::factory()->create();
    $supplierItem = activeSupplierItem();

    $response = $this->withSession(['active_profile_id' => $staff->id])
        ->post(route('transactions.store'), [
            'supplier_item_id' => $supplierItem->id,
            'type' => 'return_from_ward',
            'quantity' => 5,
            'transaction_date' => '2026-01-05',
        ]);

    $response->assertSessionHasErrors('ward_id');
    $this->assertDatabaseCount('transactions', 0);
});

test('a write-off requires a remark', function () {
    $staff = Profile::factory()->create();
    $supplierItem = activeSupplierItem();

    $response = $this->withSession(['active_profile_id' => $staff->id])
        ->post(route('transactions.store'), [
            'supplier_item_id' => $supplierItem->id,
            'type' => 'write_off',
            'quantity' => 5,
            'transaction_date' => '2026-01-05',
        ]);

    $response->assertSessionHasErrors('remark');
    $this->assertDatabaseCount('transactions', 0);
});

test('a transaction is rejected against an inactive supplier item', function () {
    $staff = Profile::factory()->create();
    $supplierItem = activeSupplierItem(['active' => false]);

    $response = $this->withSession(['active_profile_id' => $staff->id])
        ->post(route('transactions.store'), [
            'supplier_item_id' => $supplierItem->id,
            'type' => 'received',
            'quantity' => 5,
            'transaction_date' => '2026-01-05',
            'batch_number' => 'BATCH-001',
            'expiration_date' => '2026-12-31',
        ]);

    $response->assertSessionHasErrors('supplier_item_id');
    $this->assertDatabaseCount('transactions', 0);
});

test('a transaction is rejected against a future-dated contract price', function () {
    $staff = Profile::factory()->create();
    $supplierItem = activeSupplierItem([
        'effective_date' => now()->addMonth()->toDateString(),
    ]);

    $response = $this->withSession(['active_profile_id' => $staff->id])
        ->post(route('transactions.store'), [
            'supplier_item_id' => $supplierItem->id,
            'type' => 'received',
            'quantity' => 5,
            'transaction_date' => now()->toDateString(),
            'batch_number' => 'BATCH-001',
            'expiration_date' => '2026-12-31',
        ]);

    $response->assertSessionHasErrors('supplier_item_id');
    $this->assertDatabaseCount('transactions', 0);
});

test('a movement that would drive the balance below zero is rejected for staff', function () {
    $staff = Profile::factory()->create();
    $supplierItem = activeSupplierItem();

    $response = $this->withSession(['active_profile_id' => $staff->id])
        ->post(route('transactions.store'), [
            'supplier_item_id' => $supplierItem->id,
            'type' => 'consumption',
            'quantity' => 5,
            'transaction_date' => '2026-01-05',
        ]);

    $response->assertSessionHasErrors('quantity');
    $this->assertDatabaseCount('transactions', 0);
});

test('an administrator can override a negative balance with a reason', function () {
    $admin = Profile::factory()->administrator()->create();
    $supplierItem = activeSupplierItem();

    $response = $this->withSession(['active_profile_id' => $admin->id])
        ->post(route('transactions.store'), [
            'supplier_item_id' => $supplierItem->id,
            'type' => 'consumption',
            'quantity' => 5,
            'transaction_date' => '2026-01-05',
            'override_reason' => 'Stock count correction',
        ]);

    $response->assertRedirectBack();

    $this->assertDatabaseHas('transactions', [
        'supplier_item_id' => $supplierItem->id,
        'type' => 'consumption',
        'quantity' => 5,
        'override_reason' => 'Stock count correction',
    ]);
});

test('an administrator override without a reason is rejected', function () {
    $admin = Profile::factory()->administrator()->create();
    $supplierItem = activeSupplierItem();

    $response = $this->withSession(['active_profile_id' => $admin->id])
        ->post(route('transactions.store'), [
            'supplier_item_id' => $supplierItem->id,
            'type' => 'consumption',
            'quantity' => 5,
            'transaction_date' => '2026-01-05',
        ]);

    $response->assertSessionHasErrors('override_reason');
    $this->assertDatabaseCount('transactions', 0);
});

test('a transaction is rejected when its period is closed', function () {
    $staff = Profile::factory()->create();
    $supplierItem = activeSupplierItem();

    Period::factory()->create([
        'year' => 2026,
        'month' => 1,
        'status' => 'closed',
    ]);

    $response = $this->withSession(['active_profile_id' => $staff->id])
        ->post(route('transactions.store'), [
            'supplier_item_id' => $supplierItem->id,
            'type' => 'received',
            'quantity' => 5,
            'transaction_date' => '2026-01-05',
            'batch_number' => 'BATCH-001',
            'expiration_date' => '2026-12-31',
        ]);

    $response->assertSessionHasErrors('transaction_date');
    $this->assertDatabaseCount('transactions', 0);
});

test('recording a transaction opens the period for its date', function () {
    $staff = Profile::factory()->create();
    $supplierItem = activeSupplierItem();

    $this->withSession(['active_profile_id' => $staff->id])
        ->post(route('transactions.store'), [
            'supplier_item_id' => $supplierItem->id,
            'type' => 'received',
            'quantity' => 5,
            'transaction_date' => '2026-03-15',
            'batch_number' => 'BATCH-001',
            'expiration_date' => '2026-12-31',
        ]);

    $this->assertDatabaseHas('periods', [
        'year' => 2026,
        'month' => 3,
        'status' => 'open',
    ]);
});

test('recording a transaction writes an audit log attributed to the acting profile', function () {
    $staff = Profile::factory()->create();
    $supplierItem = activeSupplierItem();

    $this->withSession(['active_profile_id' => $staff->id])
        ->post(route('transactions.store'), [
            'supplier_item_id' => $supplierItem->id,
            'type' => 'received',
            'quantity' => 5,
            'transaction_date' => '2026-01-05',
            'batch_number' => 'BATCH-001',
            'expiration_date' => '2026-12-31',
        ]);

    $log = AuditLog::query()
        ->where('auditable_type', Transaction::class)
        ->sole();

    expect($log->profile_id)->toBe($staff->id);
    expect($log->action)->toBe('create');
    expect($log->after['type'])->toBe('received');
    expect($log->after['quantity'])->toBe('5.00');
});

test('the balance reflects recorded movements', function () {
    $staff = Profile::factory()->create();
    $supplierItem = activeSupplierItem(['price' => 100]);
    $ward = Ward::factory()->create();

    // The index shows the current calendar month's period, so record the
    // movements against today's date.
    $today = now()->toDateString();

    $this->withSession(['active_profile_id' => $staff->id])
        ->post(route('transactions.store'), [
            'supplier_item_id' => $supplierItem->id,
            'type' => 'received',
            'quantity' => 20,
            'transaction_date' => $today,
            'batch_number' => 'BATCH-001',
            'expiration_date' => '2026-12-31',
        ]);

    $this->withSession(['active_profile_id' => $staff->id])
        ->post(route('transactions.store'), [
            'supplier_item_id' => $supplierItem->id,
            'type' => 'consumption',
            'quantity' => 8,
            'transaction_date' => $today,
            'ward_id' => $ward->id,
        ]);

    $response = $this->withSession(['active_profile_id' => $staff->id])
        ->get(route('transactions.index'));

    $response->assertInertia(fn ($page) => $page
        ->where('supplierItems.0.balance.quantity', 12)
        ->where('supplierItems.0.balance.total_cost', 1200));
});

test('transactions cannot be edited or deleted', function () {
    $staff = Profile::factory()->create();
    $supplierItem = activeSupplierItem();

    $this->withSession(['active_profile_id' => $staff->id])
        ->post(route('transactions.store'), [
            'supplier_item_id' => $supplierItem->id,
            'type' => 'received',
            'quantity' => 5,
            'transaction_date' => '2026-01-05',
            'batch_number' => 'BATCH-001',
            'expiration_date' => '2026-12-31',
        ]);

    $transaction = Transaction::query()->sole();

    $this->withSession(['active_profile_id' => $staff->id])
        ->patch("/transactions/{$transaction->id}")
        ->assertNotFound();

    $this->withSession(['active_profile_id' => $staff->id])
        ->delete("/transactions/{$transaction->id}")
        ->assertNotFound();
});

test('the transaction screen requires an active profile', function () {
    $this->get(route('transactions.index'))->assertRedirect('/');
});
