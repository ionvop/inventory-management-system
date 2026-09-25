<?php

use App\Models\Item;
use App\Models\Profile;
use App\Models\Supplier;
use App\Models\SupplierItem;
use App\Models\Transaction;

test('an administrator can view the supplier item catalog', function () {
    $admin = Profile::factory()->administrator()->create();
    $supplier = Supplier::factory()->create(['name' => 'Nutricia']);
    $item = Item::factory()->create(['code' => 'ALITRAQ', 'description' => 'Alitraq 76g']);
    $supplierItem = SupplierItem::factory()->create([
        'supplier_id' => $supplier->id,
        'item_id' => $item->id,
        'price' => 250.50,
        'effective_date' => '2026-01-01',
    ]);

    $response = $this->withSession(['active_profile_id' => $admin->id])
        ->get(route('supplier-items.index'));

    $response->assertOk();
    $response->assertInertia(function ($page) use ($supplierItem) {
        $page->component('SupplierItems');
        $page->has('supplierItems', 1);
        $page->where('supplierItems.0.id', $supplierItem->id);
        $page->where('supplierItems.0.supplier_name', 'Nutricia');
        $page->where('supplierItems.0.item_code', 'ALITRAQ');
        $page->where('supplierItems.0.has_transactions', false);
    });
});

test('a non-administrator cannot view the supplier item catalog', function () {
    $staff = Profile::factory()->create();

    $this->withSession(['active_profile_id' => $staff->id])
        ->get(route('supplier-items.index'))
        ->assertForbidden();
});

test('an administrator can create a contract price', function () {
    $admin = Profile::factory()->administrator()->create();
    $supplier = Supplier::factory()->create();
    $item = Item::factory()->create();

    $response = $this->withSession(['active_profile_id' => $admin->id])
        ->post(route('supplier-items.store'), [
            'supplier_id' => $supplier->id,
            'item_id' => $item->id,
            'price' => 199.99,
            'effective_date' => '2026-01-01',
        ]);

    $response->assertRedirectBack();
    $this->assertDatabaseHas('supplier_items', [
        'supplier_id' => $supplier->id,
        'item_id' => $item->id,
        'price' => 199.99,
        'effective_date' => '2026-01-01',
    ]);
});

test('creating a contract price requires a supplier, item, price and date', function () {
    $admin = Profile::factory()->administrator()->create();

    $response = $this->withSession(['active_profile_id' => $admin->id])
        ->post(route('supplier-items.store'), []);

    $response->assertSessionHasErrors([
        'supplier_id',
        'item_id',
        'price',
        'effective_date',
    ]);
    $this->assertDatabaseCount('supplier_items', 0);
});

test('a duplicate supplier item effective date is rejected', function () {
    $admin = Profile::factory()->administrator()->create();
    $supplier = Supplier::factory()->create();
    $item = Item::factory()->create();

    SupplierItem::factory()->create([
        'supplier_id' => $supplier->id,
        'item_id' => $item->id,
        'effective_date' => '2026-01-01',
    ]);

    $response = $this->withSession(['active_profile_id' => $admin->id])
        ->post(route('supplier-items.store'), [
            'supplier_id' => $supplier->id,
            'item_id' => $item->id,
            'price' => 300,
            'effective_date' => '2026-01-01',
        ]);

    $response->assertSessionHasErrors('effective_date');
    $this->assertDatabaseCount('supplier_items', 1);
});

test('a price change creates a new record and preserves the original', function () {
    $admin = Profile::factory()->administrator()->create();
    $supplier = Supplier::factory()->create();
    $item = Item::factory()->create();

    $original = SupplierItem::factory()->create([
        'supplier_id' => $supplier->id,
        'item_id' => $item->id,
        'price' => 100,
        'effective_date' => '2026-01-01',
    ]);

    $this->withSession(['active_profile_id' => $admin->id])
        ->post(route('supplier-items.store'), [
            'supplier_id' => $supplier->id,
            'item_id' => $item->id,
            'price' => 125,
            'effective_date' => '2026-02-01',
        ])
        ->assertRedirectBack();

    $this->assertDatabaseCount('supplier_items', 2);
    $this->assertDatabaseHas('supplier_items', [
        'id' => $original->id,
        'price' => 100,
        'effective_date' => '2026-01-01',
    ]);
    $this->assertDatabaseHas('supplier_items', [
        'supplier_id' => $supplier->id,
        'item_id' => $item->id,
        'price' => 125,
        'effective_date' => '2026-02-01',
    ]);
});

test('updating a supplier item cannot change its price', function () {
    $admin = Profile::factory()->administrator()->create();
    $supplierItem = SupplierItem::factory()->create([
        'price' => 100,
        'effective_date' => '2026-01-01',
    ]);

    $this->withSession(['active_profile_id' => $admin->id])
        ->patch(route('supplier-items.update', $supplierItem->id), [
            'price' => 999,
            'effective_date' => '2026-01-01',
            'active' => false,
        ])
        ->assertRedirectBack();

    $supplierItem->refresh();

    expect($supplierItem->price)->toBe('100.00');
    expect($supplierItem->active)->toBeFalse();
});

test('a supplier item with transactions cannot be deleted', function () {
    $admin = Profile::factory()->administrator()->create();
    $supplierItem = SupplierItem::factory()->create();

    Transaction::factory()->create([
        'supplier_item_id' => $supplierItem->id,
        'profile_id' => $admin->id,
    ]);

    $response = $this->withSession(['active_profile_id' => $admin->id])
        ->delete(route('supplier-items.destroy', $supplierItem->id));

    $response->assertSessionHasErrors('supplier_item');
    $this->assertDatabaseHas('supplier_items', ['id' => $supplierItem->id]);
});

test('a supplier item without transactions can be deleted', function () {
    $admin = Profile::factory()->administrator()->create();
    $supplierItem = SupplierItem::factory()->create();

    $this->withSession(['active_profile_id' => $admin->id])
        ->delete(route('supplier-items.destroy', $supplierItem->id))
        ->assertRedirectBack();

    $this->assertDatabaseMissing('supplier_items', ['id' => $supplierItem->id]);
});

test('currentFor resolves the latest effective contract price', function () {
    $supplier = Supplier::factory()->create();
    $item = Item::factory()->create();

    SupplierItem::factory()->create([
        'supplier_id' => $supplier->id,
        'item_id' => $item->id,
        'price' => 100,
        'effective_date' => '2026-01-01',
    ]);
    SupplierItem::factory()->create([
        'supplier_id' => $supplier->id,
        'item_id' => $item->id,
        'price' => 125,
        'effective_date' => '2026-02-01',
    ]);

    $current = SupplierItem::currentFor($supplier->id, $item->id);

    expect($current)->not->toBeNull();
    expect($current->price)->toBe('125.00');
});

test('currentFor ignores inactive and future-dated contract prices', function () {
    $supplier = Supplier::factory()->create();
    $item = Item::factory()->create();

    SupplierItem::factory()->create([
        'supplier_id' => $supplier->id,
        'item_id' => $item->id,
        'price' => 100,
        'effective_date' => '2026-01-01',
    ]);
    SupplierItem::factory()->create([
        'supplier_id' => $supplier->id,
        'item_id' => $item->id,
        'price' => 125,
        'effective_date' => '2026-02-01',
        'active' => false,
    ]);
    SupplierItem::factory()->create([
        'supplier_id' => $supplier->id,
        'item_id' => $item->id,
        'price' => 150,
        'effective_date' => now()->addYear()->toDateString(),
    ]);

    $current = SupplierItem::currentFor($supplier->id, $item->id);

    expect($current)->not->toBeNull();
    expect($current->price)->toBe('100.00');
});

test('currentFor returns null when no active contract price exists', function () {
    $supplier = Supplier::factory()->create();
    $item = Item::factory()->create();

    expect(SupplierItem::currentFor($supplier->id, $item->id))->toBeNull();
});
