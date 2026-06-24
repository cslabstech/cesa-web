<?php

test('homepage renders successfully and opens external links in a new tab', function () {
    $response = $this->get('/');

    $response->assertStatus(200);

    // External links should have target="_blank" and rel="noopener noreferrer"
    $response->assertSee('href="https://helpdesk.completeselular.com/"', false);
    $response->assertSee('href="https://sam.mediaselularindonesia.com/"', false);
    $response->assertSee('target="_blank"', false);
    $response->assertSee('rel="noopener noreferrer"', false);

    // Internal links should also be visible
    $response->assertSee('href="/form"', false);
});
