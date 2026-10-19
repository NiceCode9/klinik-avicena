<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('tamu diarahkan ke login sebelum mengganti kata sandi', function () {
    $this->get('/ganti-kata-sandi')->assertRedirect(route('login'));
});

test('pengguna dapat membuka formulir ganti kata sandi', function () {
    $this->withoutVite();
    $this->actingAs(User::factory()->create());

    $this->get('/ganti-kata-sandi')
        ->assertOk()
        ->assertViewIs('auth.kata_sandi');
});

test('kata sandi berubah setelah data benar', function () {
    $pengguna = User::factory()->create(['password' => Hash::make('sandi-lama')]);
    $this->actingAs($pengguna);

    $respons = $this->put('/ganti-kata-sandi', [
        'kata_sandi_lama' => 'sandi-lama',
        'kata_sandi' => 'sandi-baru-123',
        'kata_sandi_confirmation' => 'sandi-baru-123',
    ]);

    $respons->assertRedirect()->assertSessionHas('sukses');
    expect(Hash::check('sandi-baru-123', $pengguna->fresh()->password))->toBeTrue();
    $this->assertDatabaseHas('sys_log_aktivitas', [
        'pengguna_id' => $pengguna->id,
        'aksi' => 'ubah',
        'nama_tabel' => 'sys_pengguna',
    ]);
});

test('kata sandi lama salah ditolak dan sandi tidak berubah', function () {
    $pengguna = User::factory()->create(['password' => Hash::make('sandi-lama')]);
    $this->actingAs($pengguna);

    $respons = $this->put('/ganti-kata-sandi', [
        'kata_sandi_lama' => 'sandi-salah',
        'kata_sandi' => 'sandi-baru-123',
        'kata_sandi_confirmation' => 'sandi-baru-123',
    ]);

    $respons->assertSessionHasErrors('kata_sandi_lama');
    expect(Hash::check('sandi-lama', $pengguna->fresh()->password))->toBeTrue();
});

test('konfirmasi kata sandi tidak cocok ditolak', function () {
    $pengguna = User::factory()->create(['password' => Hash::make('sandi-lama')]);
    $this->actingAs($pengguna);

    $respons = $this->put('/ganti-kata-sandi', [
        'kata_sandi_lama' => 'sandi-lama',
        'kata_sandi' => 'sandi-baru-123',
        'kata_sandi_confirmation' => 'beda-123',
    ]);

    $respons->assertSessionHasErrors('kata_sandi');
});

test('kata sandi baru minimal delapan karakter', function () {
    $pengguna = User::factory()->create(['password' => Hash::make('sandi-lama')]);
    $this->actingAs($pengguna);

    $respons = $this->put('/ganti-kata-sandi', [
        'kata_sandi_lama' => 'sandi-lama',
        'kata_sandi' => 'pendek',
        'kata_sandi_confirmation' => 'pendek',
    ]);

    $respons->assertSessionHasErrors('kata_sandi');
});
