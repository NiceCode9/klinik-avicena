<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests are redirected to login from the dashboard', function () {
    $respons = $this->get('/dashboard');

    $respons->assertRedirect(route('login'));
});

test('the dashboard page renders the admin layout when authenticated', function () {
    $this->withoutVite();

    $this->actingAs(User::factory()->create());

    $respons = $this->get('/dashboard');

    $respons->assertOk()
        ->assertViewIs('dashboard')
        ->assertSee('app-wrapper');
});
