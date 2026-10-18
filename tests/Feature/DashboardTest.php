<?php

test('the dashboard page renders the admin layout', function () {
    $this->withoutVite();

    $response = $this->get('/dashboard');

    $response->assertOk()
        ->assertViewIs('dashboard')
        ->assertSee('app-wrapper');
});
