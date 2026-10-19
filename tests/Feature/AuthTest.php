<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('pengguna aktif dapat login memakai username', function () {
    $pengguna = User::factory()->create(['username' => 'putri', 'aktif' => true]);

    $respons = $this->post('/login', ['username' => 'putri', 'password' => 'password']);

    $respons->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($pengguna);
    $this->assertDatabaseHas('sys_log_aktivitas', ['pengguna_id' => $pengguna->id, 'aksi' => 'login']);
});

test('halaman login tampil untuk tamu', function () {
    $this->withoutVite();

    $respons = $this->get('/login');

    $respons->assertOk()->assertSee('Username');
});

test('kredensial salah ditolak', function () {
    $pengguna = User::factory()->create();

    $respons = $this->post('/login', [
        'username' => $pengguna->username,
        'password' => 'salah-banget',
    ]);

    $respons->assertSessionHasErrors('username');
    $this->assertGuest();
});

test('akun nonaktif ditolak dengan pesan khusus', function () {
    $pengguna = User::factory()->create(['aktif' => false]);

    $respons = $this->post('/login', [
        'username' => $pengguna->username,
        'password' => 'password',
    ]);

    $respons->assertSessionHasErrors('username');
    expect($this->app['session.store']->get('errors')->first('username'))->toContain('nonaktif');
    $this->assertGuest();
});

test('ingat saya menyetel cookie tetap masuk', function () {
    $pengguna = User::factory()->create();

    $respons = $this->post('/login', [
        'username' => $pengguna->username,
        'password' => 'password',
        'ingat' => '1',
    ]);

    $cookie = collect($respons->headers->getCookies())
        ->first(fn ($c) => str_starts_with($c->getName(), 'remember_web_'));
    expect($cookie)->not->toBeNull()->and($cookie->getValue())->not->toBeNull();
});

test('logout mengakhiri sesi', function () {
    $this->actingAs(User::factory()->create());

    $respons = $this->post('/logout');

    $respons->assertRedirect(route('login'));
    $this->assertGuest();
});

test('login sukses mengisi waktu login terakhir', function () {
    $pengguna = User::factory()->create(['username' => 'putri']);
    expect($pengguna->login_terakhir)->toBeNull();

    $this->post('/login', ['username' => 'putri', 'password' => 'password']);

    expect($pengguna->fresh()->login_terakhir)->not->toBeNull();
});

test('percobaan login dibatasi throttle', function () {
    User::factory()->create(['username' => 'putri']);

    foreach (range(1, 5) as $percobaan) {
        $this->post('/login', ['username' => 'putri', 'password' => 'salah']);
    }

    $respons = $this->post('/login', ['username' => 'putri', 'password' => 'password']);

    $respons->assertSessionHasErrors('username');
    expect($respons->getSession()->get('errors')->first('username'))->toContain('Terlalu banyak');
    $this->assertGuest();
});

test('pengguna nonaktif dengan sesi lama ditolak di dashboard', function () {
    $this->actingAs(User::factory()->create(['aktif' => false]));

    $this->get('/dashboard')->assertForbidden();
});

test('pesan validasi tampil dalam bahasa indonesia', function () {
    $respons = $this->post('/login', ['username' => '', 'password' => '']);

    $respons->assertSessionHasErrors('username');
    expect($respons->getSession()->get('errors')->first('username'))->toContain('wajib diisi');
});

test('username berbentuk array tidak menyebabkan error server', function () {
    $respons = $this->post('/login', ['username' => ['admin'], 'password' => 'rahasia']);

    $respons->assertSessionHasErrors('username');
    $this->assertGuest();
});

test('logout dicatat di log aktivitas', function () {
    $pengguna = User::factory()->create();
    $this->actingAs($pengguna);

    $this->post('/logout');

    $this->assertDatabaseHas('sys_log_aktivitas', [
        'pengguna_id' => $pengguna->id,
        'aksi' => 'logout',
    ]);
});
