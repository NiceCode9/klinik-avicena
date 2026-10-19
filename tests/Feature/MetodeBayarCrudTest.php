<?php

use App\Models\Master\MetodeBayar;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    buatMenu('dasbor', 'dashboard', 'Dasbor');
    buatMenu('master.metode_bayar', 'metode-bayar.index', 'Metode Bayar');
});

test('pengguna dengan hak dapat membuka daftar metode bayar', function () {
    MetodeBayar::create(['nama' => 'Tunai']);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.metode_bayar']))
        ->get(route('metode-bayar.index'))
        ->assertOk()
        ->assertSee('Tunai');
});

test('pengguna tanpa hak ditolak membuka daftar metode bayar', function () {
    $this->actingAs(penggunaDenganIzin(['dasbor']))
        ->get(route('metode-bayar.index'))
        ->assertForbidden();
});

test('pengguna dengan hak dapat menambah metode bayar', function () {
    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.metode_bayar']))
        ->post(route('metode-bayar.store'), ['nama' => 'Transfer', 'aktif' => '1'])
        ->assertRedirect(route('metode-bayar.index'));

    $this->assertDatabaseHas('mtr_metode_bayar', ['nama' => 'Transfer', 'aktif' => 1]);
});

test('nama metode bayar wajib dan unik', function () {
    MetodeBayar::create(['nama' => 'Tunai']);
    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.metode_bayar']));

    $this->post(route('metode-bayar.store'), ['nama' => ''])->assertSessionHasErrors('nama');
    $this->post(route('metode-bayar.store'), ['nama' => 'Tunai'])->assertSessionHasErrors('nama');
});

test('pengguna dengan hak dapat mengubah metode bayar', function () {
    $metode = MetodeBayar::create(['nama' => 'Tunai']);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.metode_bayar']))
        ->put(route('metode-bayar.update', $metode), ['nama' => 'Tunai (kasir)', 'aktif' => '1'])
        ->assertRedirect(route('metode-bayar.index'));

    $this->assertDatabaseHas('mtr_metode_bayar', ['id' => $metode->id, 'nama' => 'Tunai (kasir)']);
});

test('pengguna dengan hak dapat menghapus metode bayar (soft delete)', function () {
    $metode = MetodeBayar::create(['nama' => 'Transfer']);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.metode_bayar']))
        ->delete(route('metode-bayar.destroy', $metode))
        ->assertRedirect(route('metode-bayar.index'));

    $this->assertSoftDeleted('mtr_metode_bayar', ['id' => $metode->id]);
});
