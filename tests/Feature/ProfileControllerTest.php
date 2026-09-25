<?php

use App\Models\Profile;
use App\Models\Transaction;

test('renders the profile picker with non-deleted profiles', function () {
    $alice = Profile::factory()->create(['name' => 'Alice', 'role' => 'staff']);
    $deleted = Profile::factory()->create(['name' => 'Retired', 'role' => 'staff']);
    $deleted->delete();

    $response = $this->get(route('profiles.index'));

    $response->assertOk();
    $response->assertInertia(function ($page) use ($alice) {
        $page->component('ProfilePicker');
        $page->has('profiles', 1);
        $page->where('profiles.0.id', $alice->id);
        $page->missing('canManage');
    });
});

test('selecting a profile stores it in the session and redirects to the dashboard', function () {
    $profile = Profile::factory()->create();

    $response = $this->post(route('profiles.select', $profile->id));

    $response->assertRedirectToRoute('dashboard');
    $response->assertSessionHas('active_profile_id', $profile->id);
});

test('rejects selecting a soft-deleted profile', function () {
    $profile = Profile::factory()->create();
    $profile->delete();

    $response = $this->post(route('profiles.select', $profile->id));

    $response->assertNotFound();
});

test('any user can create a profile', function () {
    $staff = Profile::factory()->create(['role' => 'staff']);

    $response = $this->withSession(['active_profile_id' => $staff->id])
        ->post(route('profiles.store'), [
            'name' => 'New Staff',
            'role' => 'staff',
        ]);

    $response->assertRedirectBack();
    $this->assertDatabaseHas('profiles', ['name' => 'New Staff', 'role' => 'staff']);
});

test('a profile can be created without an active profile selected', function () {
    $response = $this->post(route('profiles.store'), [
        'name' => 'First Profile',
        'role' => 'administrator',
    ]);

    $response->assertRedirectBack();
    $this->assertDatabaseHas('profiles', ['name' => 'First Profile', 'role' => 'administrator']);
});

test('any user can update a profile', function () {
    $staff = Profile::factory()->create(['role' => 'staff']);
    $profile = Profile::factory()->create(['name' => 'Old Name']);

    $response = $this->withSession(['active_profile_id' => $staff->id])
        ->patch(route('profiles.update', $profile->id), [
            'name' => 'New Name',
            'role' => 'supervisor',
        ]);

    $response->assertRedirectBack();
    $this->assertDatabaseHas('profiles', ['id' => $profile->id, 'name' => 'New Name', 'role' => 'supervisor']);
});

test('any user can soft-delete a profile', function () {
    $staff = Profile::factory()->create(['role' => 'staff']);
    $profile = Profile::factory()->create(['name' => 'To Delete']);

    $response = $this->withSession(['active_profile_id' => $staff->id])
        ->delete(route('profiles.destroy', $profile->id));

    $response->assertRedirectBack();
    $this->assertSoftDeleted('profiles', ['id' => $profile->id]);
});

test('soft-deleting a profile preserves its transaction history', function () {
    $profile = Profile::factory()->create(['name' => 'Has History']);

    Transaction::factory()->create([
        'period_id' => 1,
        'supplier_item_id' => 1,
        'type' => 'received',
        'quantity' => 1,
        'total_cost' => 1,
        'transaction_date' => '2026-01-01',
        'profile_id' => $profile->id,
    ]);

    $response = $this->delete(route('profiles.destroy', $profile->id));

    $response->assertRedirectBack();
    $this->assertSoftDeleted('profiles', ['id' => $profile->id]);
    $this->assertDatabaseHas('transactions', ['profile_id' => $profile->id]);
});

test('logging out clears the active profile and returns to the picker', function () {
    $profile = Profile::factory()->create();

    $response = $this->withSession(['active_profile_id' => $profile->id])
        ->post(route('profiles.logout'));

    $response->assertRedirectToRoute('profiles.index');
    $response->assertSessionMissing('active_profile_id');
});
