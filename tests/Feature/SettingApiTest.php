<?php

use App\Models\Setting;
use App\Models\User;

it('returns settings as a key-value map', function () {
    actingAsUser();
    Setting::factory()->create(['key' => 'store_name', 'value' => 'My Store']);
    Setting::factory()->create(['key' => 'currency', 'value' => 'USD']);

    $this->getJson('/api/settings')
        ->assertOk()
        ->assertJsonPath('data.store_name', 'My Store')
        ->assertJsonPath('data.currency', 'USD');
});

it('returns an empty map when there are no settings', function () {
    actingAsUser();

    $this->getJson('/api/settings')
        ->assertOk()
        ->assertJsonPath('data', []);
});

it('upserts settings', function () {
    actingAsUser();

    $this->putJson('/api/settings', ['store_name' => 'My Store', 'currency' => 'USD'])
        ->assertOk()
        ->assertJsonPath('data.store_name', 'My Store')
        ->assertJsonPath('data.currency', 'USD');

    $this->assertDatabaseHas('settings', ['key' => 'store_name', 'value' => 'My Store']);
    $this->assertDatabaseHas('settings', ['key' => 'currency', 'value' => 'USD']);
});

it('updates an existing setting rather than duplicating it', function () {
    actingAsUser();
    Setting::factory()->create(['key' => 'store_name', 'value' => 'Old Name']);

    $this->putJson('/api/settings', ['store_name' => 'New Name'])
        ->assertOk()
        ->assertJsonPath('data.store_name', 'New Name');

    $this->assertDatabaseCount('settings', 1);
    $this->assertDatabaseHas('settings', ['key' => 'store_name', 'value' => 'New Name']);
});