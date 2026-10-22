<?php

use App\Models\Bid;
use App\Models\Item;
use App\Models\Profile;
use App\Models\Supplier;
use App\Models\SupplierItem;

test('a supervisor can view the bid tracking screen', function () {
    $supervisor = Profile::factory()->supervisor()->create();
    $supplier = Supplier::factory()->create(['name' => 'Zuellig']);
    $item = Item::factory()->create(['code' => 'ALITRAQ']);
    SupplierItem::factory()->create([
        'supplier_id' => $supplier->id,
        'item_id' => $item->id,
        'effective_date' => '2026-01-01',
        'active' => true,
    ]);

    $response = $this->withSession(['active_profile_id' => $supervisor->id])
        ->get(route('bids.index'));

    $response->assertOk();
    $response->assertInertia(function ($page) {
        $page->component('Bids');
        $page->has('rows', 1);
        $page->where('rows.0.supplier_name', 'Zuellig');
        $page->where('rows.0.item_code', 'ALITRAQ');
        $page->where('rows.0.has_bid', false);
        $page->has('reorderThreshold');
    });
});

test('an administrator can view the bid tracking screen', function () {
    $admin = Profile::factory()->administrator()->create();

    $this->withSession(['active_profile_id' => $admin->id])
        ->get(route('bids.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Bids'));
});

test('a staff profile cannot view the bid tracking screen', function () {
    $staff = Profile::factory()->create();

    $this->withSession(['active_profile_id' => $staff->id])
        ->get(route('bids.index'))
        ->assertForbidden();
});

test('a supervisor can set a bid quantity', function () {
    $supervisor = Profile::factory()->supervisor()->create();
    $supplier = Supplier::factory()->create();
    $item = Item::factory()->create();

    $response = $this->withSession(['active_profile_id' => $supervisor->id])
        ->post(route('bids.store'), [
            'supplier_id' => $supplier->id,
            'item_id' => $item->id,
            'bid_quantity' => 10000,
            'set_date' => '2026-02-01',
        ]);

    $response->assertRedirectBack();
    $response->assertSessionHas('success', 'Bid quantity set.');
    $this->assertDatabaseHas('bids', [
        'supplier_id' => $supplier->id,
        'item_id' => $item->id,
        'bid_quantity' => 10000,
        'set_date' => '2026-02-01',
        'profile_id' => $supervisor->id,
    ]);
});

test('setting a bid requires a supplier, item, quantity and date', function () {
    $supervisor = Profile::factory()->supervisor()->create();

    $response = $this->withSession(['active_profile_id' => $supervisor->id])
        ->post(route('bids.store'), []);

    $response->assertSessionHasErrors([
        'supplier_id',
        'item_id',
        'bid_quantity',
        'set_date',
    ]);
    $this->assertDatabaseCount('bids', 0);
});

test('a bid quantity must be greater than zero', function () {
    $supervisor = Profile::factory()->supervisor()->create();
    $supplier = Supplier::factory()->create();
    $item = Item::factory()->create();

    $response = $this->withSession(['active_profile_id' => $supervisor->id])
        ->post(route('bids.store'), [
            'supplier_id' => $supplier->id,
            'item_id' => $item->id,
            'bid_quantity' => 0,
            'set_date' => '2026-02-01',
        ]);

    $response->assertSessionHasErrors('bid_quantity');
    $this->assertDatabaseCount('bids', 0);
});

test('setting a new bid preserves the previous bid as history', function () {
    $supervisor = Profile::factory()->supervisor()->create();
    $supplier = Supplier::factory()->create();
    $item = Item::factory()->create();

    $original = Bid::factory()->create([
        'supplier_id' => $supplier->id,
        'item_id' => $item->id,
        'bid_quantity' => 10000,
        'set_date' => '2026-01-01',
    ]);

    $this->withSession(['active_profile_id' => $supervisor->id])
        ->post(route('bids.store'), [
            'supplier_id' => $supplier->id,
            'item_id' => $item->id,
            'bid_quantity' => 12000,
            'set_date' => '2026-07-01',
        ])
        ->assertRedirectBack();

    $this->assertDatabaseCount('bids', 2);
    $this->assertDatabaseHas('bids', [
        'id' => $original->id,
        'bid_quantity' => 10000,
        'set_date' => '2026-01-01',
    ]);
    $this->assertDatabaseHas('bids', [
        'supplier_id' => $supplier->id,
        'item_id' => $item->id,
        'bid_quantity' => 12000,
        'set_date' => '2026-07-01',
    ]);
});

test('a staff profile cannot set a bid quantity', function () {
    $staff = Profile::factory()->create();
    $supplier = Supplier::factory()->create();
    $item = Item::factory()->create();

    $this->withSession(['active_profile_id' => $staff->id])
        ->post(route('bids.store'), [
            'supplier_id' => $supplier->id,
            'item_id' => $item->id,
            'bid_quantity' => 10000,
            'set_date' => '2026-02-01',
        ])
        ->assertForbidden();

    $this->assertDatabaseCount('bids', 0);
});

test('setting a bid is recorded in the audit log', function () {
    $supervisor = Profile::factory()->supervisor()->create();
    $supplier = Supplier::factory()->create();
    $item = Item::factory()->create();

    $this->withSession(['active_profile_id' => $supervisor->id])
        ->post(route('bids.store'), [
            'supplier_id' => $supplier->id,
            'item_id' => $item->id,
            'bid_quantity' => 10000,
            'set_date' => '2026-02-01',
        ]);

    $this->assertDatabaseHas('audit_logs', [
        'profile_id' => $supervisor->id,
        'auditable_type' => (new Bid)->getMorphClass(),
        'action' => 'create',
    ]);
});
