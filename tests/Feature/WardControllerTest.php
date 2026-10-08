<?php

use App\Models\Profile;
use App\Models\Transaction;
use App\Models\Ward;

test('an administrator can view the ward catalog', function () {
    $admin = Profile::factory()->administrator()->create();
    $ward = Ward::factory()->create(['name' => 'Pediatrics']);

    $response = $this->withSession(['active_profile_id' => $admin->id])
        ->get(route('wards.index'));

    $response->assertOk();
    $response->assertInertia(function ($page) use ($ward) {
        $page->component('Wards');
        $page->has('wards', 1);
        $page->where('wards.0.id', $ward->id);
        $page->where('wards.0.name', 'Pediatrics');
        $page->where('wards.0.has_transactions', false);
    });
});

test('an administrator can create a ward', function () {
    $admin = Profile::factory()->administrator()->create();

    $response = $this->withSession(['active_profile_id' => $admin->id])
        ->post(route('wards.store'), ['name' => 'Cardiology']);

    $response->assertRedirectBack();
    $response->assertSessionHas('success', 'Ward created.');
    $this->assertDatabaseHas('wards', ['name' => 'Cardiology']);
});

test('creating a ward requires a name', function () {
    $admin = Profile::factory()->administrator()->create();

    $response = $this->withSession(['active_profile_id' => $admin->id])
        ->post(route('wards.store'), ['name' => '']);

    $response->assertSessionHasErrors('name');
    $this->assertDatabaseCount('wards', 0);
});

test('a ward name must be unique', function () {
    $admin = Profile::factory()->administrator()->create();
    Ward::factory()->create(['name' => 'Pediatrics']);

    $response = $this->withSession(['active_profile_id' => $admin->id])
        ->post(route('wards.store'), ['name' => 'Pediatrics']);

    $response->assertSessionHasErrors('name');
    $this->assertDatabaseCount('wards', 1);
});

test('an administrator can update a ward', function () {
    $admin = Profile::factory()->administrator()->create();
    $ward = Ward::factory()->create(['name' => 'Old Name', 'active' => true]);

    $response = $this->withSession(['active_profile_id' => $admin->id])
        ->patch(route('wards.update', $ward->id), [
            'name' => 'New Name',
            'active' => false,
        ]);

    $response->assertRedirectBack();
    $response->assertSessionHas('success', 'Ward updated.');
    $this->assertDatabaseHas('wards', [
        'id' => $ward->id,
        'name' => 'New Name',
        'active' => false,
    ]);
});

test('updating a ward keeps its own name valid', function () {
    $admin = Profile::factory()->administrator()->create();
    $ward = Ward::factory()->create(['name' => 'Pediatrics']);

    $response = $this->withSession(['active_profile_id' => $admin->id])
        ->patch(route('wards.update', $ward->id), [
            'name' => 'Pediatrics',
            'active' => true,
        ]);

    $response->assertRedirectBack();
    $response->assertSessionHasNoErrors();
});

test('an administrator can delete a ward without transactions', function () {
    $admin = Profile::factory()->administrator()->create();
    $ward = Ward::factory()->create();

    $response = $this->withSession(['active_profile_id' => $admin->id])
        ->delete(route('wards.destroy', $ward->id));

    $response->assertRedirectBack();
    $this->assertDatabaseMissing('wards', ['id' => $ward->id]);
});

test('a ward with transactions cannot be deleted', function () {
    $admin = Profile::factory()->administrator()->create();
    $ward = Ward::factory()->create();
    Transaction::factory()->create(['ward_id' => $ward->id]);

    $response = $this->withSession(['active_profile_id' => $admin->id])
        ->delete(route('wards.destroy', $ward->id));

    $response->assertRedirectBack();
    $response->assertSessionHasErrors('ward');
    $this->assertDatabaseHas('wards', ['id' => $ward->id]);
});

test('a staff profile cannot manage wards', function () {
    $staff = Profile::factory()->create(['role' => 'staff']);

    $this->withSession(['active_profile_id' => $staff->id])
        ->get(route('wards.index'))
        ->assertForbidden();

    $this->withSession(['active_profile_id' => $staff->id])
        ->post(route('wards.store'), ['name' => 'Nope'])
        ->assertForbidden();

    $this->assertDatabaseCount('wards', 0);
});

test('a supervisor profile cannot manage wards', function () {
    $supervisor = Profile::factory()->supervisor()->create();

    $this->withSession(['active_profile_id' => $supervisor->id])
        ->get(route('wards.index'))
        ->assertForbidden();
});

test('ward routes redirect to the picker without an active profile', function () {
    $this->get(route('wards.index'))->assertRedirect('/');
});
