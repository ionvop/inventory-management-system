<?php

use App\Models\Profile;

test('the dashboard redirects to the picker without an active profile', function () {
    $this->get(route('dashboard'))->assertRedirect('/');
});

test('the dashboard is reachable with an active profile', function () {
    $profile = Profile::factory()->create();

    $this->withSession(['active_profile_id' => $profile->id])
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Dashboard'));
});

test('a soft-deleted active profile is treated as no active profile', function () {
    $profile = Profile::factory()->create();
    $profile->delete();

    $this->withSession(['active_profile_id' => $profile->id])
        ->get(route('dashboard'))
        ->assertRedirect('/');
});

test('the picker renders when the active profile was soft-deleted', function () {
    $profile = Profile::factory()->create();
    $profile->delete();

    $this->withSession(['active_profile_id' => $profile->id])
        ->get(route('profiles.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('ProfilePicker')
            ->where('activeProfile', null));
});

test('a malformed active profile id is treated as no active profile', function () {
    $this->withSession(['active_profile_id' => 'not-a-number'])
        ->get(route('dashboard'))
        ->assertRedirect('/');
});

test('the active profile is shared with Inertia', function () {
    $profile = Profile::factory()->administrator()->create(['name' => 'Dana']);

    $this->withSession(['active_profile_id' => $profile->id])
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('activeProfile.id', $profile->id)
            ->where('activeProfile.name', 'Dana')
            ->where('activeProfile.role', 'administrator'));
});

test('no active profile is shared when the session is empty', function () {
    $this->get(route('profiles.index'))
        ->assertInertia(fn ($page) => $page->where('activeProfile', null));
});
