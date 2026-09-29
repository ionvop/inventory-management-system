<?php

use App\Models\AuditLog;
use App\Models\Item;
use App\Models\Profile;
use App\Models\Supplier;

test('an administrator can view the audit log', function () {
    $admin = Profile::factory()->administrator()->create();

    AuditLog::factory()->create([
        'profile_id' => $admin->id,
        'auditable_type' => Supplier::class,
        'auditable_id' => 7,
        'action' => 'create',
        'after' => ['name' => 'Abbott Nutrition'],
    ]);

    $response = $this->withSession(['active_profile_id' => $admin->id])
        ->get(route('audit-logs.index'));

    $response->assertOk();
    $response->assertInertia(function ($page) use ($admin) {
        $page->component('AuditLogs');
        $page->has('logs.data', 1);
        $page->where('logs.data.0.profile_name', $admin->name);
        $page->where('logs.data.0.action', 'create');
        $page->where('logs.data.0.record_type', 'Supplier');
        $page->where('logs.data.0.record_type_label', 'Supplier');
        $page->where('logs.data.0.auditable_id', 7);
        $page->where('logs.data.0.label', 'Abbott Nutrition');
    });
});

test('a staff profile cannot view the audit log', function () {
    $staff = Profile::factory()->create();

    $this->withSession(['active_profile_id' => $staff->id])
        ->get(route('audit-logs.index'))
        ->assertForbidden();
});

test('a supervisor cannot view the audit log', function () {
    $supervisor = Profile::factory()->supervisor()->create();

    $this->withSession(['active_profile_id' => $supervisor->id])
        ->get(route('audit-logs.index'))
        ->assertForbidden();
});

test('the audit log can be filtered by profile', function () {
    $admin = Profile::factory()->administrator()->create();
    $other = Profile::factory()->create();

    AuditLog::factory()->create(['profile_id' => $admin->id, 'action' => 'create']);
    AuditLog::factory()->create(['profile_id' => $other->id, 'action' => 'create']);

    $response = $this->withSession(['active_profile_id' => $admin->id])
        ->get(route('audit-logs.index', ['profile_id' => $other->id]));

    $response->assertInertia(function ($page) use ($other) {
        $page->has('logs.data', 1);
        $page->where('logs.data.0.profile_id', $other->id);
        $page->where('filters.profile_id', $other->id);
    });
});

test('the audit log can be filtered by record type', function () {
    $admin = Profile::factory()->administrator()->create();

    AuditLog::factory()->create(['auditable_type' => Supplier::class]);
    AuditLog::factory()->create(['auditable_type' => Item::class]);

    $response = $this->withSession(['active_profile_id' => $admin->id])
        ->get(route('audit-logs.index', ['type' => 'Item']));

    $response->assertInertia(function ($page) {
        $page->has('logs.data', 1);
        $page->where('logs.data.0.record_type', 'Item');
    });
});

test('the audit log can be filtered by action', function () {
    $admin = Profile::factory()->administrator()->create();

    AuditLog::factory()->create(['action' => 'create']);
    AuditLog::factory()->create(['action' => 'delete']);

    $response = $this->withSession(['active_profile_id' => $admin->id])
        ->get(route('audit-logs.index', ['action' => 'delete']));

    $response->assertInertia(function ($page) {
        $page->has('logs.data', 1);
        $page->where('logs.data.0.action', 'delete');
    });
});

test('the audit log can be filtered by date range', function () {
    $admin = Profile::factory()->administrator()->create();

    AuditLog::factory()->create([
        'action' => 'create',
        'created_at' => '2026-01-15 10:00:00',
    ]);
    AuditLog::factory()->create([
        'action' => 'create',
        'created_at' => '2026-03-20 10:00:00',
    ]);

    $response = $this->withSession(['active_profile_id' => $admin->id])
        ->get(route('audit-logs.index', [
            'from' => '2026-03-01',
            'to' => '2026-03-31',
        ]));

    $response->assertInertia(function ($page) {
        $page->has('logs.data', 1);
        $page->where('logs.data.0.created_at', '2026-03-20 10:00:00');
    });
});

test('the audit log resolves the name of a deleted profile', function () {
    $admin = Profile::factory()->administrator()->create();
    $deleted = Profile::factory()->create(['name' => 'Former Staff']);
    $deleted->delete();

    AuditLog::factory()->create(['profile_id' => $deleted->id]);

    $response = $this->withSession(['active_profile_id' => $admin->id])
        ->get(route('audit-logs.index'));

    $response->assertInertia(function ($page) {
        $page->where('logs.data.0.profile_name', 'Former Staff');
        $page->has('filterOptions.profiles', 2);
    });
});

test('the audit log reports only the fields that changed', function () {
    $admin = Profile::factory()->administrator()->create();

    AuditLog::factory()->create([
        'action' => 'update',
        'before' => ['name' => 'Old Name', 'active' => true],
        'after' => ['name' => 'New Name', 'active' => true],
    ]);

    $response = $this->withSession(['active_profile_id' => $admin->id])
        ->get(route('audit-logs.index'));

    $response->assertInertia(function ($page) {
        $page->has('logs.data.0.changes', 1);
        $page->where('logs.data.0.changes.0.field', 'name');
        $page->where('logs.data.0.changes.0.before', 'Old Name');
        $page->where('logs.data.0.changes.0.after', 'New Name');
    });
});

test('the audit log lists the available filter options', function () {
    $admin = Profile::factory()->administrator()->create();

    AuditLog::factory()->create(['action' => 'create']);
    AuditLog::factory()->create(['action' => 'close']);

    $response = $this->withSession(['active_profile_id' => $admin->id])
        ->get(route('audit-logs.index'));

    $response->assertInertia(function ($page) {
        $page->has('filterOptions.recordTypes', 7);
        $page->has('filterOptions.actions', 2);
    });
});
