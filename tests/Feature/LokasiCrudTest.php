<?php

use App\Models\Master\Lokasi;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    buatMenu('dasbor', 'dashboard', 'Dasbor');
    buatMenu('master.lokasi', 'lokasi.index', 'Lokasi');
});

test('tamu dialihkan ke halaman login', function () {
    $this->get(route('lokasi.index'))->assertRedirect(route('login'));
});

test('pengguna dengan hak dapat membuka daftar lokasi', function () {
    Lokasi::create(['kode' => 'APT', 'nama' => 'Apotek']);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.lokasi']))
        ->get(route('lokasi.index'))
        ->assertOk()
        ->assertSee('Apotek');
});

test('pengguna tanpa hak ditolak membuka daftar lokasi', function () {
    $this->actingAs(penggunaDenganIzin(['dasbor']))
        ->get(route('lokasi.index'))
        ->assertForbidden();
});

test('pengguna dengan hak dapat menambah lokasi', function () {
    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.lokasi']))
        ->post(route('lokasi.store'), [
            'kode' => 'APT',
            'nama' => 'Apotek',
            'ihs_location_id' => 'loc-123',
            'aktif' => '1',
        ])
        ->assertRedirect(route('lokasi.index'));

    $this->assertDatabaseHas('mtr_lokasi', [
        'kode' => 'APT',
        'nama' => 'Apotek',
        'ihs_location_id' => 'loc-123',
        'aktif' => 1,
    ]);
});

test('kode lokasi wajib, unik, dan maksimal 10 karakter', function () {
    Lokasi::create(['kode' => 'APT', 'nama' => 'Apotek']);
    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.lokasi']));

    $this->post(route('lokasi.store'), ['kode' => '', 'nama' => ''])->assertSessionHasErrors(['kode', 'nama']);
    $this->post(route('lokasi.store'), ['kode' => 'APT', 'nama' => 'Apotek Lain'])->assertSessionHasErrors('kode');
    $this->post(route('lokasi.store'), ['kode' => 'KODEPANJANG', 'nama' => 'Lokasi Lain'])->assertSessionHasErrors('kode');
});

test('pengguna dengan hak dapat mengubah lokasi', function () {
    $lokasi = Lokasi::create(['kode' => 'APT', 'nama' => 'Apotek']);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.lokasi']))
        ->put(route('lokasi.update', $lokasi), [
            'kode' => 'APT',
            'nama' => 'Apotek Utama',
            'ihs_location_id' => 'loc-999',
            'aktif' => '1',
        ])
        ->assertRedirect(route('lokasi.index'));

    $this->assertDatabaseHas('mtr_lokasi', [
        'id' => $lokasi->id,
        'nama' => 'Apotek Utama',
        'ihs_location_id' => 'loc-999',
    ]);
});

test('pengguna dengan hak dapat menghapus lokasi (soft delete)', function () {
    $lokasi = Lokasi::create(['kode' => 'APT', 'nama' => 'Apotek']);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.lokasi']))
        ->delete(route('lokasi.destroy', $lokasi))
        ->assertRedirect(route('lokasi.index'));

    $this->assertSoftDeleted('mtr_lokasi', ['id' => $lokasi->id]);
});
