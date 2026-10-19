<?php

use App\Models\Master\KategoriObat;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    buatMenu('dasbor', 'dashboard', 'Dasbor');
    buatMenu('master.kategori_obat', 'kategori-obat.index', 'Kategori Obat');
});

test('pengguna dengan hak dapat membuka daftar kategori obat', function () {
    KategoriObat::create(['nama' => 'Antibiotik']);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.kategori_obat']))
        ->get(route('kategori-obat.index'))
        ->assertOk()
        ->assertSee('Antibiotik');
});

test('pengguna tanpa hak ditolak membuka daftar kategori obat', function () {
    $this->actingAs(penggunaDenganIzin(['dasbor']))
        ->get(route('kategori-obat.index'))
        ->assertForbidden();
});

test('pengguna dengan hak dapat menambah kategori obat', function () {
    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.kategori_obat']))
        ->post(route('kategori-obat.store'), ['nama' => 'Analgesik'])
        ->assertRedirect(route('kategori-obat.index'));

    $this->assertDatabaseHas('mtr_kategori_obat', ['nama' => 'Analgesik']);
});

test('nama kategori obat wajib dan unik', function () {
    KategoriObat::create(['nama' => 'Antibiotik']);
    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.kategori_obat']));

    $this->post(route('kategori-obat.store'), ['nama' => ''])->assertSessionHasErrors('nama');
    $this->post(route('kategori-obat.store'), ['nama' => 'Antibiotik'])->assertSessionHasErrors('nama');
});

test('pengguna dengan hak dapat mengubah kategori obat', function () {
    $kategori = KategoriObat::create(['nama' => 'Antibiotik']);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.kategori_obat']))
        ->put(route('kategori-obat.update', $kategori), ['nama' => 'Antibiotik Sistemik'])
        ->assertRedirect(route('kategori-obat.index'));

    $this->assertDatabaseHas('mtr_kategori_obat', ['id' => $kategori->id, 'nama' => 'Antibiotik Sistemik']);
});

test('pengguna dengan hak dapat menghapus kategori obat (soft delete)', function () {
    $kategori = KategoriObat::create(['nama' => 'Vitamin']);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.kategori_obat']))
        ->delete(route('kategori-obat.destroy', $kategori))
        ->assertRedirect(route('kategori-obat.index'));

    $this->assertSoftDeleted('mtr_kategori_obat', ['id' => $kategori->id]);
});
