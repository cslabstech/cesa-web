<?php

test('example', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('filament.admin.auth.login'));
});
