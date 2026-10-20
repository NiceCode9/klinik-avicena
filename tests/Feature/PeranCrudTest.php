<?php

use App\Models\Sys\HakAkses;
use App\Models\Sys\Peran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    buatMenu('dasbor', 'dashboard', 'Dasbor');
    buatMenu('sistem.peran', 'peran.index', 'Peran');
});

test('tamu dialihkan ke halaman login', function () {
    $this->get(route('peran.index'))->assertRedirect(route('login'));
});

test('pengguna dengan hak dapat membuka daftar peran', function () {
    $peran = Peran::create(['nama' => 'kasir', 'keterangan' => 'Petugas kasir']);
    User::factory()->create(['peran_id' => $peran->id]);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'sistem.peran']))
        ->get(route('peran.index'))
        ->assertOk()
        ->assertSee('kasir')
        ->assertSee('Petugas kasir');
});

test('pengguna tanpa hak ditolak membuka daftar peran', function () {
    $this->actingAs(penggunaDenganIzin(['dasbor']))
        ->get(route('peran.index'))
        ->assertForbidden();
});

test('pengguna dengan hak dapat menambah peran', function () {
    $this->actingAs(penggunaDenganIzin(['dasbor', 'sistem.peran']))
        ->post(route('peran.store'), ['nama' => 'apoteker', 'keterangan' => 'Petugas farmasi'])
        ->assertRedirect(route('peran.index'))
        ->assertSessionHas('sukses');

    $this->assertDatabaseHas('sys_peran', ['nama' => 'apoteker', 'keterangan' => 'Petugas farmasi']);
});

test('nama peran wajib diisi, unik, dan maksimal 50 karakter', function () {
    Peran::create(['nama' => 'kasir']);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'sistem.peran']));

    $this->post(route('peran.store'), ['nama' => ''])->assertSessionHasErrors('nama');
    $this->post(route('peran.store'), ['nama' => 'kasir'])->assertSessionHasErrors('nama');
    $this->post(route('peran.store'), ['nama' => str_repeat('a', 51)])->assertSessionHasErrors('nama');
});

test('pengguna dengan hak dapat mengubah peran', function () {
    $peran = Peran::create(['nama' => 'kasir']);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'sistem.peran']))
        ->put(route('peran.update', $peran), ['nama' => 'kasir utama', 'keterangan' => 'Diubah'])
        ->assertRedirect(route('peran.index'));

    $this->assertDatabaseHas('sys_peran', ['id' => $peran->id, 'nama' => 'kasir utama', 'keterangan' => 'Diubah']);
});

test('nama peran admin tidak boleh diubah', function () {
    $admin = Peran::create(['nama' => 'admin']);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'sistem.peran']))
        ->put(route('peran.update', $admin), ['nama' => 'admin baru', 'keterangan' => 'x'])
        ->assertSessionHas('gagal');

    $this->assertDatabaseHas('sys_peran', ['id' => $admin->id, 'nama' => 'admin']);
});

test('peran admin tidak boleh dihapus', function () {
    $admin = Peran::create(['nama' => 'admin']);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'sistem.peran']))
        ->delete(route('peran.destroy', $admin))
        ->assertSessionHas('gagal');

    $this->assertDatabaseHas('sys_peran', ['id' => $admin->id]);
});

test('peran yang masih dipakai pengguna tidak boleh dihapus', function () {
    $peran = Peran::create(['nama' => 'kasir']);
    User::factory()->create(['peran_id' => $peran->id]);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'sistem.peran']))
        ->delete(route('peran.destroy', $peran))
        ->assertSessionHas('gagal');

    $this->assertDatabaseHas('sys_peran', ['id' => $peran->id]);
});

test('peran yang tidak dipakai dihapus beserta hak aksesnya', function () {
    $peran = Peran::create(['nama' => 'kasir']);
    HakAkses::create([
        'peran_id' => $peran->id,
        'menu_id' => buatMenu('sistem.peran')->id,
        'bisa_lihat' => true,
        'bisa_tambah' => false,
        'bisa_ubah' => false,
        'bisa_hapus' => false,
    ]);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'sistem.peran']))
        ->delete(route('peran.destroy', $peran))
        ->assertRedirect(route('peran.index'))
        ->assertSessionHas('sukses');

    $this->assertDatabaseMissing('sys_peran', ['id' => $peran->id]);
    $this->assertDatabaseMissing('sys_hak_akses', ['peran_id' => $peran->id]);
});
