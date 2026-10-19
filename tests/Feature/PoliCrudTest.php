<?php

use App\Models\Master\Poli;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    buatMenu('dasbor', 'dashboard', 'Dasbor');
    buatMenu('master.poli', 'poli.index', 'Poli');
});

test('pengguna dengan hak dapat membuka daftar poli', function () {
    Poli::create(['kode' => 'INT', 'nama' => 'Poli Umum']);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.poli']))
        ->get(route('poli.index'))
        ->assertOk()
        ->assertSee('Poli Umum');
});

test('pengguna tanpa hak ditolak membuka daftar poli', function () {
    $this->actingAs(penggunaDenganIzin(['dasbor']))
        ->get(route('poli.index'))
        ->assertForbidden();
});

test('pengguna dengan hak dapat menambah poli', function () {
    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.poli']))
        ->post(route('poli.store'), ['kode' => 'GIG', 'nama' => 'Poli Gigi', 'aktif' => '1'])
        ->assertRedirect(route('poli.index'));

    $this->assertDatabaseHas('mtr_poli', ['kode' => 'GIG', 'nama' => 'Poli Gigi', 'aktif' => 1]);
});

test('kode poli wajib, unik, dan maksimal 5 karakter', function () {
    Poli::create(['kode' => 'INT', 'nama' => 'Poli Umum']);
    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.poli']));

    $this->post(route('poli.store'), ['kode' => '', 'nama' => ''])->assertSessionHasErrors(['kode', 'nama']);
    $this->post(route('poli.store'), ['kode' => 'INT', 'nama' => 'Poli Lain'])->assertSessionHasErrors('kode');
    $this->post(route('poli.store'), ['kode' => 'PANJANG', 'nama' => 'Poli Lain'])->assertSessionHasErrors('kode');
});

test('pengguna dengan hak dapat mengubah poli', function () {
    $poli = Poli::create(['kode' => 'INT', 'nama' => 'Poli Umum']);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.poli']))
        ->put(route('poli.update', $poli), ['kode' => 'INT', 'nama' => 'Poli Dalam', 'aktif' => '1'])
        ->assertRedirect(route('poli.index'));

    $this->assertDatabaseHas('mtr_poli', ['id' => $poli->id, 'nama' => 'Poli Dalam']);
});

test('pengguna dengan hak dapat menghapus poli (soft delete)', function () {
    $poli = Poli::create(['kode' => 'SRF', 'nama' => 'Poli Saraf']);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.poli']))
        ->delete(route('poli.destroy', $poli))
        ->assertRedirect(route('poli.index'));

    $this->assertSoftDeleted('mtr_poli', ['id' => $poli->id]);
});
