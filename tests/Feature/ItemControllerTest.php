<?php

use App\Models\Item;
use App\Models\Profile;

test('an administrator can view the item catalog', function () {
    $admin = Profile::factory()->administrator()->create();
    $item = Item::factory()->create([
        'code' => 'ITM-0001',
        'description' => 'Alitraq 76g sachet',
        'unit' => 'sachet',
    ]);

    $response = $this->withSession(['active_profile_id' => $admin->id])
        ->get(route('items.index'));

    $response->assertOk();
    $response->assertInertia(function ($page) use ($item) {
        $page->component('Items');
        $page->has('items', 1);
        $page->where('items.0.id', $item->id);
        $page->where('items.0.code', 'ITM-0001');
        $page->where('items.0.unit', 'sachet');
    });
});

test('the item catalog excludes soft-deleted items', function () {
    $admin = Profile::factory()->administrator()->create();
    Item::factory()->create();
    Item::factory()->create()->delete();

    $response = $this->withSession(['active_profile_id' => $admin->id])
        ->get(route('items.index'));

    $response->assertInertia(fn ($page) => $page->has('items', 1));
});

test('an administrator can create an item', function () {
    $admin = Profile::factory()->administrator()->create();

    $response = $this->withSession(['active_profile_id' => $admin->id])
        ->post(route('items.store'), [
            'code' => 'ITM-0100',
            'description' => 'Nepro HP',
            'unit' => 'can',
        ]);

    $response->assertRedirectBack();
    $this->assertDatabaseHas('items', [
        'code' => 'ITM-0100',
        'description' => 'Nepro HP',
        'unit' => 'can',
    ]);
});

test('creating an item requires code, description and unit', function () {
    $admin = Profile::factory()->administrator()->create();

    $response = $this->withSession(['active_profile_id' => $admin->id])
        ->post(route('items.store'), ['code' => '']);

    $response->assertSessionHasErrors(['code', 'description', 'unit']);
    $this->assertDatabaseCount('items', 0);
});

test('an administrator can update an item', function () {
    $admin = Profile::factory()->administrator()->create();
    $item = Item::factory()->create(['description' => 'Old', 'active' => true]);

    $response = $this->withSession(['active_profile_id' => $admin->id])
        ->patch(route('items.update', $item->id), [
            'code' => $item->code,
            'description' => 'New',
            'unit' => 'box',
            'active' => false,
        ]);

    $response->assertRedirectBack();
    $this->assertDatabaseHas('items', [
        'id' => $item->id,
        'description' => 'New',
        'unit' => 'box',
        'active' => false,
    ]);
});

test('an administrator can soft-delete an item', function () {
    $admin = Profile::factory()->administrator()->create();
    $item = Item::factory()->create();

    $response = $this->withSession(['active_profile_id' => $admin->id])
        ->delete(route('items.destroy', $item->id));

    $response->assertRedirectBack();
    $this->assertSoftDeleted('items', ['id' => $item->id]);
});

test('a staff profile cannot manage items', function () {
    $staff = Profile::factory()->create(['role' => 'staff']);

    $this->withSession(['active_profile_id' => $staff->id])
        ->get(route('items.index'))
        ->assertForbidden();

    $this->withSession(['active_profile_id' => $staff->id])
        ->post(route('items.store'), [
            'code' => 'ITM-9999',
            'description' => 'Nope',
            'unit' => 'box',
        ])
        ->assertForbidden();

    $this->assertDatabaseCount('items', 0);
});

test('item routes redirect to the picker without an active profile', function () {
    $this->get(route('items.index'))->assertRedirect('/');
});
