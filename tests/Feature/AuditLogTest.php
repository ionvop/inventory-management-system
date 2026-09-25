<?php

use App\Models\AuditLog;
use App\Models\Item;
use App\Models\Profile;
use App\Models\Supplier;
use App\Models\SupplierItem;

test('creating a supplier writes an audit log attributed to the acting profile', function () {
    $admin = Profile::factory()->administrator()->create();

    $this->withSession(['active_profile_id' => $admin->id])
        ->post(route('suppliers.store'), ['name' => 'Abbott Nutrition'])
        ->assertRedirectBack();

    $log = AuditLog::query()->sole();

    expect($log->profile_id)->toBe($admin->id);
    expect($log->action)->toBe('create');
    expect($log->auditable_type)->toBe(Supplier::class);
    expect($log->before)->toBeNull();
    expect($log->after['name'])->toBe('Abbott Nutrition');
});

test('updating a supplier records the before and after values', function () {
    $admin = Profile::factory()->administrator()->create();
    $supplier = Supplier::factory()->create(['name' => 'Old Name']);

    $this->withSession(['active_profile_id' => $admin->id])
        ->patch(route('suppliers.update', $supplier->id), [
            'name' => 'New Name',
            'active' => true,
        ])
        ->assertRedirectBack();

    $log = AuditLog::query()->sole();

    expect($log->action)->toBe('update');
    expect($log->before['name'])->toBe('Old Name');
    expect($log->after['name'])->toBe('New Name');
});

test('deleting a supplier records the removed values', function () {
    $admin = Profile::factory()->administrator()->create();
    $supplier = Supplier::factory()->create(['name' => 'Retired Co']);

    $this->withSession(['active_profile_id' => $admin->id])
        ->delete(route('suppliers.destroy', $supplier->id))
        ->assertRedirectBack();

    $log = AuditLog::query()->sole();

    expect($log->action)->toBe('delete');
    expect($log->before['name'])->toBe('Retired Co');
    expect($log->after)->toBeNull();
});

test('creating an item writes an audit log', function () {
    $admin = Profile::factory()->administrator()->create();

    $this->withSession(['active_profile_id' => $admin->id])
        ->post(route('items.store'), [
            'code' => 'NEPRO-HP',
            'description' => 'Nepro HP',
            'unit' => 'can',
        ])
        ->assertRedirectBack();

    $log = AuditLog::query()->sole();

    expect($log->profile_id)->toBe($admin->id);
    expect($log->auditable_type)->toBe(Item::class);
    expect($log->after['code'])->toBe('NEPRO-HP');
});

test('creating a contract price writes an audit log', function () {
    $admin = Profile::factory()->administrator()->create();
    $supplier = Supplier::factory()->create();
    $item = Item::factory()->create();

    $this->withSession(['active_profile_id' => $admin->id])
        ->post(route('supplier-items.store'), [
            'supplier_id' => $supplier->id,
            'item_id' => $item->id,
            'price' => 250,
            'effective_date' => '2026-01-01',
        ])
        ->assertRedirectBack();

    $log = AuditLog::query()->sole();

    expect($log->profile_id)->toBe($admin->id);
    expect($log->auditable_type)->toBe(SupplierItem::class);
    expect($log->after['price'])->toBe('250.00');
});

test('profile management is audited even without an active profile', function () {
    $this->post(route('profiles.store'), [
        'name' => 'First User',
        'role' => 'administrator',
    ])->assertRedirectBack();

    $log = AuditLog::query()->sole();

    expect($log->profile_id)->toBeNull();
    expect($log->auditable_type)->toBe(Profile::class);
    expect($log->after['name'])->toBe('First User');
});

test('profile management is attributed to the acting profile when one is selected', function () {
    $admin = Profile::factory()->administrator()->create();

    $this->withSession(['active_profile_id' => $admin->id])
        ->post(route('profiles.store'), [
            'name' => 'Second User',
            'role' => 'staff',
        ])
        ->assertRedirectBack();

    $log = AuditLog::query()->sole();

    expect($log->profile_id)->toBe($admin->id);
    expect($log->after['role'])->toBe('staff');
});
