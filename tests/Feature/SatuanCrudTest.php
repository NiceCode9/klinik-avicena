<?php

use App\Models\Master\Satuan;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    buatMenu('dasbor', 'dashboard', 'Dasbor');
    buatMenu('master.satuan', 'satuan.index', 'Satuan');
});

test('tamu dialihkan ke halaman login', function () {
    $this->get(route('satuan.index'))->assertRedirect(route('login'));
});

test('pengguna dengan hak dapat membuka daftar satuan', function () {
    Satuan::create(['nama' => 'Tablet']);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.satuan']))
        ->get(route('satuan.index'))
        ->assertOk()
        ->assertSee('Tablet');
});

test('pengguna tanpa hak ditolak membuka daftar satuan', function () {
    $this->actingAs(penggunaDenganIzin(['dasbor']))
        ->get(route('satuan.index'))
        ->assertForbidden();
});

test('pengguna dengan hak dapat menambah satuan', function () {
    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.satuan']))
        ->post(route('satuan.store'), ['nama' => 'Kapsul'])
        ->assertRedirect(route('satuan.index'));

    $this->assertDatabaseHas('mtr_satuan', ['nama' => 'Kapsul']);
});

test('pengguna tanpa hak tambah tidak dapat menyimpan satuan', function () {
    $this->actingAs(penggunaDenganIzin(['dasbor']))
        ->post(route('satuan.store'), ['nama' => 'Kapsul'])
        ->assertForbidden();

    $this->assertDatabaseMissing('mtr_satuan', ['nama' => 'Kapsul']);
});

test('nama satuan wajib diisi dan tidak boleh duplikat', function () {
    Satuan::create(['nama' => 'Tablet']);
    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.satuan']));

    $this->post(route('satuan.store'), ['nama' => ''])
        ->assertSessionHasErrors('nama');

    $this->post(route('satuan.store'), ['nama' => 'Tablet'])
        ->assertSessionHasErrors('nama');
});

test('pengguna dengan hak dapat mengubah satuan', function () {
    $satuan = Satuan::create(['nama' => 'Tablet']);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.satuan']))
        ->put(route('satuan.update', $satuan), ['nama' => 'Tablet Kunyah'])
        ->assertRedirect(route('satuan.index'));

    $this->assertDatabaseHas('mtr_satuan', ['id' => $satuan->id, 'nama' => 'Tablet Kunyah']);
});

test('pengguna tanpa hak ubah tidak dapat mengubah satuan', function () {
    $satuan = Satuan::create(['nama' => 'Tablet']);

    $this->actingAs(penggunaDenganIzin(['dasbor']))
        ->put(route('satuan.update', $satuan), ['nama' => 'Diubah'])
        ->assertForbidden();

    $this->assertDatabaseHas('mtr_satuan', ['id' => $satuan->id, 'nama' => 'Tablet']);
});

test('pengguna dengan hak dapat menghapus satuan (soft delete)', function () {
    $satuan = Satuan::create(['nama' => 'Tablet']);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.satuan']))
        ->delete(route('satuan.destroy', $satuan))
        ->assertRedirect(route('satuan.index'));

    $this->assertSoftDeleted('mtr_satuan', ['id' => $satuan->id]);
});
