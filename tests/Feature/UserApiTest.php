<?php

use App\Models\User;

// The users resource is public (no resolve.user middleware).

it('lists users with pagination', function () {
    User::factory()->count(3)->create();

    $response = $this->getJson('/api/users');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [['id', 'username', 'created_at', 'updated_at']],
            'pagination' => ['page', 'limit', 'total', 'total_pages'],
        ])
        ->assertJsonPath('pagination.total', 3);
});

it('searches users by username', function () {
    User::factory()->create(['username' => 'maria']);
    User::factory()->create(['username' => 'juan']);

    $this->getJson('/api/users?search=mar')
        ->assertOk()
        ->assertJsonPath('pagination.total', 1)
        ->assertJsonPath('data.0.username', 'maria');
});

it('sorts users by username descending', function () {
    User::factory()->create(['username' => 'alpha']);
    User::factory()->create(['username' => 'beta']);

    $this->getJson('/api/users?sort=username&order=desc')
        ->assertOk()
        ->assertJsonPath('data.0.username', 'beta');
});

it('creates a user', function () {
    $this->postJson('/api/users', ['username' => 'new_user'])
        ->assertCreated()
        ->assertJsonPath('data.username', 'new_user');

    $this->assertDatabaseHas('users', ['username' => 'new_user']);
});

it('rejects creating a user without a username', function () {
    $this->postJson('/api/users', [])
        ->assertStatus(400)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');
});

it('rejects creating a user with a duplicate username', function () {
    User::factory()->create(['username' => 'taken']);

    $this->postJson('/api/users', ['username' => 'taken'])
        ->assertStatus(400)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');
});

it('shows a user', function () {
    $user = User::factory()->create(['username' => 'maria']);

    $this->getJson("/api/users/{$user->id}")
        ->assertOk()
        ->assertJsonPath('data.username', 'maria');
});

it('returns 404 for a missing user', function () {
    $this->getJson('/api/users/9999')
        ->assertStatus(404)
        ->assertJsonPath('error.code', 'NOT_FOUND');
});

it('updates a user', function () {
    $user = User::factory()->create(['username' => 'old_name']);

    $this->putJson("/api/users/{$user->id}", ['username' => 'new_name'])
        ->assertOk()
        ->assertJsonPath('data.username', 'new_name');

    $this->assertDatabaseHas('users', ['username' => 'new_name']);
});

it('allows a user to keep their own username on update', function () {
    $user = User::factory()->create(['username' => 'maria']);

    $this->putJson("/api/users/{$user->id}", ['username' => 'maria'])
        ->assertOk()
        ->assertJsonPath('data.username', 'maria');
});

it('rejects updating a user to a duplicate username', function () {
    $user = User::factory()->create(['username' => 'maria']);
    User::factory()->create(['username' => 'juan']);

    $this->putJson("/api/users/{$user->id}", ['username' => 'juan'])
        ->assertStatus(400)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');
});

it('deletes a user without transactions', function () {
    $user = User::factory()->create();

    $this->deleteJson("/api/users/{$user->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});

it('refuses to delete a user that has transactions', function () {
    $user = User::factory()->create();
    \App\Models\Transaction::factory()->create(['user_id' => $user->id]);

    $this->deleteJson("/api/users/{$user->id}")
        ->assertStatus(409)
        ->assertJsonPath('error.code', 'USER_HAS_TRANSACTIONS');

    $this->assertDatabaseHas('users', ['id' => $user->id]);
});