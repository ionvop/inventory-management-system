<?php

use App\Models\Batch;
use App\Models\Profile;
use App\Models\Setting;
use App\Services\SettingsService;

test('an administrator can view the settings screen', function () {
    $admin = Profile::factory()->administrator()->create();

    $response = $this->withSession(['active_profile_id' => $admin->id])
        ->get(route('settings.index'));

    $response->assertOk();
    $response->assertInertia(function ($page) {
        $page->component('Settings');
        $page->has('settings', 3);
        $page->where('settings.0.key', 'near_expiry_days');
        $page->where('settings.0.default', 90);
        $page->where('settings.0.value', 90);
        $page->where('settings.0.is_overridden', false);
        $page->where('settings.1.key', 'low_stock_threshold');
        $page->where('settings.1.default', 10);
        $page->where('settings.2.key', 'bid_reorder_threshold');
        $page->where('settings.2.default', 1000);
    });
});

test('saving a setting stores an override and applies it to config', function () {
    $admin = Profile::factory()->administrator()->create();

    $response = $this->withSession(['active_profile_id' => $admin->id])
        ->post(route('settings.store'), ['near_expiry_days' => 30]);

    $response->assertRedirectBack();
    $response->assertSessionHas('success', 'Settings saved.');
    $this->assertDatabaseHas('settings', [
        'key' => 'near_expiry_days',
        'value' => '30',
    ]);
    expect(config('inventory.near_expiry_days'))->toBe(30);
});

test('an override is reflected on the settings screen', function () {
    $admin = Profile::factory()->administrator()->create();
    Setting::factory()->create(['key' => 'low_stock_threshold', 'value' => '5']);

    $response = $this->withSession(['active_profile_id' => $admin->id])
        ->get(route('settings.index'));

    $response->assertInertia(function ($page) {
        $page->where('settings.1.value', 5);
        $page->where('settings.1.is_overridden', true);
    });
});

test('an overridden threshold changes batch expiry status', function () {
    $admin = Profile::factory()->administrator()->create();

    $batch = Batch::factory()->create([
        'expiration_date' => now()->addDays(10)->toDateString(),
    ]);

    // The default 90-day window flags the batch as near expiry.
    expect($batch->expiryStatus())->toBe(Batch::STATUS_NEAR_EXPIRY);

    $this->withSession(['active_profile_id' => $admin->id])
        ->post(route('settings.store'), ['near_expiry_days' => 5]);

    // Narrowing the window to 5 days clears the flag.
    expect($batch->fresh()->expiryStatus())->toBe(Batch::STATUS_ACTIVE);
});

test('saving unchanged values reports that nothing changed', function () {
    $admin = Profile::factory()->administrator()->create();

    $response = $this->withSession(['active_profile_id' => $admin->id])
        ->post(route('settings.store'), ['near_expiry_days' => 90]);

    $response->assertRedirectBack();
    $response->assertSessionHas('success', 'No settings changed.');
    $this->assertDatabaseCount('settings', 0);
});

test('a setting cannot be negative', function () {
    $admin = Profile::factory()->administrator()->create();

    $response = $this->withSession(['active_profile_id' => $admin->id])
        ->post(route('settings.store'), ['near_expiry_days' => -1]);

    $response->assertSessionHasErrors('near_expiry_days');
    $this->assertDatabaseCount('settings', 0);
});

test('a setting must be numeric', function () {
    $admin = Profile::factory()->administrator()->create();

    $response = $this->withSession(['active_profile_id' => $admin->id])
        ->post(route('settings.store'), ['near_expiry_days' => 'soon']);

    $response->assertSessionHasErrors('near_expiry_days');
    $this->assertDatabaseCount('settings', 0);
});

test('a blank field is ignored when saving', function () {
    $admin = Profile::factory()->administrator()->create();

    $response = $this->withSession(['active_profile_id' => $admin->id])
        ->post(route('settings.store'), ['near_expiry_days' => '']);

    $response->assertRedirectBack();
    $this->assertDatabaseCount('settings', 0);
});

test('an administrator can reset a single setting to its default', function () {
    $admin = Profile::factory()->administrator()->create();
    Setting::factory()->create(['key' => 'near_expiry_days', 'value' => '30']);

    $response = $this->withSession(['active_profile_id' => $admin->id])
        ->post(route('settings.reset'), ['key' => 'near_expiry_days']);

    $response->assertRedirectBack();
    $response->assertSessionHas('success', 'Setting reset to its default.');
    $this->assertDatabaseMissing('settings', ['key' => 'near_expiry_days']);
    expect(config('inventory.near_expiry_days'))->toBe(90);
});

test('resetting an unknown setting is rejected', function () {
    $admin = Profile::factory()->administrator()->create();

    $response = $this->withSession(['active_profile_id' => $admin->id])
        ->post(route('settings.reset'), ['key' => 'nonsense']);

    $response->assertSessionHasErrors('setting');
});

test('an administrator can reset every setting to its default', function () {
    $admin = Profile::factory()->administrator()->create();
    Setting::factory()->create(['key' => 'near_expiry_days', 'value' => '30']);
    Setting::factory()->create(['key' => 'low_stock_threshold', 'value' => '5']);

    $response = $this->withSession(['active_profile_id' => $admin->id])
        ->post(route('settings.reset'));

    $response->assertRedirectBack();
    $response->assertSessionHas('success', 'All settings reset to their defaults.');
    $this->assertDatabaseCount('settings', 0);
    expect(config('inventory.near_expiry_days'))->toBe(90)
        ->and(config('inventory.low_stock_threshold'))->toBe(10.0);
});

test('changing a setting is recorded on the audit trail', function () {
    $admin = Profile::factory()->administrator()->create();

    $this->withSession(['active_profile_id' => $admin->id])
        ->post(route('settings.store'), ['near_expiry_days' => 30]);

    $this->assertDatabaseHas('audit_logs', [
        'profile_id' => $admin->id,
        'auditable_type' => Setting::class,
        'action' => 'update',
    ]);
});

test('the settings service reports defaults and effective values', function () {
    $settings = app(SettingsService::class);

    expect($settings->definitions())->toHaveKey('near_expiry_days')
        ->and($settings->defaults()['near_expiry_days'])->toBe(90)
        ->and($settings->effective()['near_expiry_days'])->toBe(90);

    Setting::factory()->create(['key' => 'near_expiry_days', 'value' => '45']);

    expect($settings->overrides()['near_expiry_days'])->toBe(45)
        ->and($settings->effective()['near_expiry_days'])->toBe(45)
        ->and($settings->defaults()['near_expiry_days'])->toBe(90);
});

test('a non-administrator cannot view or change settings', function () {
    $supervisor = Profile::factory()->supervisor()->create();

    $this->withSession(['active_profile_id' => $supervisor->id])
        ->get(route('settings.index'))
        ->assertForbidden();

    $this->withSession(['active_profile_id' => $supervisor->id])
        ->post(route('settings.store'), ['near_expiry_days' => 30])
        ->assertForbidden();

    $this->withSession(['active_profile_id' => $supervisor->id])
        ->post(route('settings.reset'))
        ->assertForbidden();
});
