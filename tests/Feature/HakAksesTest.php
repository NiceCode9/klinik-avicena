<?php

use App\Models\Sys\Peran;
use App\Services\IzinService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    buatMenu('dasbor', 'dashboard', 'Dasbor');
    buatMenu('sistem.hak_akses', 'hak-akses.index', 'Hak Akses');
});

test('pengguna dengan hak dapat membuka halaman hak akses', function () {
    $this->withoutVite();
    $this->actingAs(penggunaDenganIzin(['dasbor', 'sistem.hak_akses']));

    $this->get(route('hak-akses.index'))->assertOk();
});

test('pengguna tanpa hak ditolak', function () {
    $this->actingAs(penggunaDenganIzin(['dasbor']));

    $this->get(route('hak-akses.index'))->assertForbidden();
});

test('pengguna nonaktif ditolak', function () {
    $this->actingAs(penggunaDenganIzin(['dasbor', 'sistem.hak_akses'], ['aktif' => false]));

    $this->get(route('hak-akses.index'))->assertForbidden();
});

test('menyimpan hak akses memperbarui data dan cache izin', function () {
    $admin = penggunaDenganIzin(['dasbor', 'sistem.hak_akses']);
    $target = Peran::create(['nama' => 'resepsionis']);
    $pasien = buatMenu('pendaftaran.pasien', 'dashboard', 'Data Pasien');
    $obat = buatMenu('master.obat', 'dashboard', 'Obat');

    expect(IzinService::untukPeran($target->id))->toBe([]);

    $this->actingAs($admin)->post(route('hak-akses.simpan', $target), [
        'izin' => [
            $pasien->id => ['lihat' => 1],
            $obat->id => ['tambah' => 1],
        ],
    ])->assertRedirect();

    $this->assertDatabaseHas('sys_hak_akses', [
        'peran_id' => $target->id,
        'menu_id' => $pasien->id,
        'bisa_lihat' => true,
    ]);
    $this->assertDatabaseHas('sys_hak_akses', [
        'peran_id' => $target->id,
        'menu_id' => $obat->id,
        'bisa_lihat' => true,
        'bisa_tambah' => true,
    ]);

    $izin = IzinService::untukPeran($target->id);
    expect($izin['master.obat']['lihat'])->toBeTrue()
        ->and($izin['master.obat']['tambah'])->toBeTrue();
});

test('simpan hak akses dicatat di log aktivitas', function () {
    $admin = penggunaDenganIzin(['dasbor', 'sistem.hak_akses']);
    $target = Peran::create(['nama' => 'kasir']);

    $this->actingAs($admin)->post(route('hak-akses.simpan', $target), ['izin' => []]);

    $this->assertDatabaseHas('sys_log_aktivitas', [
        'pengguna_id' => $admin->id,
        'aksi' => 'ubah',
        'nama_tabel' => 'sys_hak_akses',
        'data_id' => $target->id,
    ]);
});

test('sidebar hanya menampilkan menu yang boleh dilihat', function () {
    $this->withoutVite();

    $this->actingAs(penggunaDenganIzin(['dasbor']))
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Dasbor')
        ->assertDontSee('Hak Akses');

    $this->actingAs(penggunaDenganIzin(['dasbor', 'sistem.hak_akses']))
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Hak Akses');
});
