<?php

use App\Models\User;

it('returns 400 when the X-User-Id header is missing', function () {
    $this->getJson('/api/items')
        ->assertStatus(400)
        ->assertJsonPath('error.code', 'MISSING_USER_ID');
});

it('returns 404 when the X-User-Id header references an unknown user', function () {
    $this->withHeader('X-User-Id', '9999')
        ->getJson('/api/items')
        ->assertStatus(404)
        ->assertJsonPath('error.code', 'USER_NOT_FOUND');
});

it('lets a valid user through the middleware', function () {
    $user = User::factory()->create();

    $this->withHeader('X-User-Id', (string) $user->id)
        ->getJson('/api/items')
        ->assertOk();
});

it('resolves the authenticated user for the request', function () {
    $user = User::factory()->create();

    $this->withHeader('X-User-Id', (string) $user->id)
        ->getJson('/api/dashboard/summary')
        ->assertOk();
});