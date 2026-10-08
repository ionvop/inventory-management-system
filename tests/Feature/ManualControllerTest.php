<?php

use App\Models\Profile;

test('the manual redirects to the profile picker without an active profile', function () {
    $this->get('/manual')->assertRedirect('/');
});

test('the manual renders with sections and a default selection', function () {
    $profile = Profile::factory()->create();

    $this->withSession(['active_profile_id' => $profile->id])
        ->get('/manual')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Manual')
            ->has('sections')
            ->where('selectedSlug', 'introduction')
            ->where('content', fn (string $content): bool => str_contains($content, 'Introduction'))
        );
});

test('the manual lists every markdown section in order', function () {
    $profile = Profile::factory()->create();

    $files = glob(resource_path('docs/manual/*.md'));
    expect($files)->not->toBeFalse();

    $this->withSession(['active_profile_id' => $profile->id])
        ->get('/manual')
        ->assertInertia(fn ($page) => $page
            ->has('sections', count($files))
            ->where('sections.0.slug', 'introduction')
            ->where('sections.1.slug', 'getting-started')
        );
});

test('a requested section is selected and rendered', function () {
    $profile = Profile::factory()->create();

    $this->withSession(['active_profile_id' => $profile->id])
        ->get('/manual?section=reports')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Manual')
            ->where('selectedSlug', 'reports')
            ->where('content', fn (string $content): bool => str_contains($content, 'Exporting to Excel'))
        );
});

test('an unknown section falls back to the first section', function () {
    $profile = Profile::factory()->create();

    $this->withSession(['active_profile_id' => $profile->id])
        ->get('/manual?section=does-not-exist')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('selectedSlug', 'introduction')
        );
});

test('the manual is available to every role', function (string $role) {
    $profile = Profile::factory()->create(['role' => $role]);

    $this->withSession(['active_profile_id' => $profile->id])
        ->get('/manual')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Manual'));
})->with(['staff', 'supervisor', 'administrator']);
