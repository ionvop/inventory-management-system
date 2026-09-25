<?php

use App\Models\Profile;
use App\Models\Supplier;

test('an administrator can view the supplier catalog', function () {
    $admin = Profile::factory()->administrator()->create();
    $supplier = Supplier::factory()->create(['name' => 'Nutricia']);

    $response = $this->withSession(['active_profile_id' => $admin->id])
        ->get(route('suppliers.index'));

    $response->assertOk();
    $response->assertInertia(function ($page) use ($supplier) {
        $page->component('Suppliers');
        $page->has('suppliers', 1);
        $page->where('suppliers.0.id', $supplier->id);
        $page->where('suppliers.0.name', 'Nutricia');
    });
});

test('the supplier catalog excludes soft-deleted suppliers', function () {
    $admin = Profile::factory()->administrator()->create();
    Supplier::factory()->create(['name' => 'Active Co']);
    Supplier::factory()->create(['name' => 'Retired Co'])->delete();

    $response = $this->withSession(['active_profile_id' => $admin->id])
        ->get(route('suppliers.index'));

    $response->assertInertia(fn ($page) => $page->has('suppliers', 1));
});

test('an administrator can create a supplier', function () {
    $admin = Profile::factory()->administrator()->create();

    $response = $this->withSession(['active_profile_id' => $admin->id])
        ->post(route('suppliers.store'), [
            'name' => 'Abbott Nutrition',
            'contract_status' => 'New contract',
        ]);

    $response->assertRedirectBack();
    $this->assertDatabaseHas('suppliers', [
        'name' => 'Abbott Nutrition',
        'contract_status' => 'New contract',
    ]);
});

test('creating a supplier requires a name', function () {
    $admin = Profile::factory()->administrator()->create();

    $response = $this->withSession(['active_profile_id' => $admin->id])
        ->post(route('suppliers.store'), ['name' => '']);

    $response->assertSessionHasErrors('name');
    $this->assertDatabaseCount('suppliers', 0);
});

test('an administrator can update a supplier', function () {
    $admin = Profile::factory()->administrator()->create();
    $supplier = Supplier::factory()->create(['name' => 'Old Name', 'active' => true]);

    $response = $this->withSession(['active_profile_id' => $admin->id])
        ->patch(route('suppliers.update', $supplier->id), [
            'name' => 'New Name',
            'contract_status' => 'Old contract',
            'active' => false,
        ]);

    $response->assertRedirectBack();
    $this->assertDatabaseHas('suppliers', [
        'id' => $supplier->id,
        'name' => 'New Name',
        'contract_status' => 'Old contract',
        'active' => false,
    ]);
});

test('an administrator can soft-delete a supplier', function () {
    $admin = Profile::factory()->administrator()->create();
    $supplier = Supplier::factory()->create();

    $response = $this->withSession(['active_profile_id' => $admin->id])
        ->delete(route('suppliers.destroy', $supplier->id));

    $response->assertRedirectBack();
    $this->assertSoftDeleted('suppliers', ['id' => $supplier->id]);
});

test('a staff profile cannot manage suppliers', function () {
    $staff = Profile::factory()->create(['role' => 'staff']);

    $this->withSession(['active_profile_id' => $staff->id])
        ->get(route('suppliers.index'))
        ->assertForbidden();

    $this->withSession(['active_profile_id' => $staff->id])
        ->post(route('suppliers.store'), ['name' => 'Nope'])
        ->assertForbidden();

    $this->assertDatabaseCount('suppliers', 0);
});

test('a supervisor profile cannot manage suppliers', function () {
    $supervisor = Profile::factory()->supervisor()->create();

    $this->withSession(['active_profile_id' => $supervisor->id])
        ->get(route('suppliers.index'))
        ->assertForbidden();
});

test('supplier routes redirect to the picker without an active profile', function () {
    $this->get(route('suppliers.index'))->assertRedirect('/');
});
