<?php

test('returns a successful response', function () {
    $response = $this->get(route('profiles.index'));

    $response->assertOk();
});
