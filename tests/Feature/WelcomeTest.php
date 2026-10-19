<?php

use App\Models\User;

test('halaman welcome menampilkan info klinik kepada tamu', function () {
    $this->withoutVite();

    $respons = $this->get('/');

    $respons->assertOk()
        ->assertSee('Avicena Farma')
        ->assertSee('Dr. Abdul Ghofir, Sp.PD')
        ->assertSee('06.30-20.00')
        ->assertSee('Masuk ke Sistem')
        ->assertSee(route('login'));
});

test('tombol di welcome menjadi Buka Aplikasi ketika sudah login', function () {
    $this->withoutVite();
    $this->actingAs(User::factory()->create());

    $respons = $this->get('/');

    $respons->assertOk()->assertSee('Buka Aplikasi');
});
