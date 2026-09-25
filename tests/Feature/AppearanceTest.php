<?php

test('the root view defaults to the system appearance when no cookie is set', function () {
    $response = $this->get(route('profiles.index'));

    $response->assertOk();
    $response->assertViewHas('appearance', 'system');
});

test('the root view reflects the appearance cookie', function () {
    $response = $this->withUnencryptedCookie('appearance', 'dark')
        ->get(route('profiles.index'));

    $response->assertOk();
    $response->assertViewHas('appearance', 'dark');
});

test('the dark class is rendered on the html element when the appearance cookie is dark', function () {
    $response = $this->withUnencryptedCookie('appearance', 'dark')
        ->get(route('profiles.index'));

    $response->assertOk();
    $response->assertSee('class="dark"', false);
});

test('the dark class is not rendered when the appearance cookie is light', function () {
    $response = $this->withUnencryptedCookie('appearance', 'light')
        ->get(route('profiles.index'));

    $response->assertOk();
    $response->assertDontSee('class="dark"', false);
});
