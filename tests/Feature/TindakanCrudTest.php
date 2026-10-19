<?php

use App\Models\Master\Poli;
use App\Models\Master\Tindakan;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    buatMenu('dasbor', 'dashboard', 'Dasbor');
    buatMenu('master.tindakan', 'tindakan.index', 'Tindakan Medis');
});

test('pengguna dengan hak dapat membuka daftar tindakan', function () {
    Tindakan::create(['kode' => 'TND-01', 'nama' => 'Nebulizer', 'tarif' => 75000]);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.tindakan']))
        ->get(route('tindakan.index'))
        ->assertOk()
        ->assertSee('Nebulizer');
});

test('pengguna tanpa hak ditolak membuka daftar tindakan', function () {
    $this->actingAs(penggunaDenganIzin(['dasbor']))
        ->get(route('tindakan.index'))
        ->assertForbidden();
});

test('pengguna dengan hak dapat menambah tindakan dengan poli', function () {
    $poli = Poli::create(['kode' => 'INT', 'nama' => 'Poli Umum']);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.tindakan']))
        ->post(route('tindakan.store'), [
            'kode' => 'TND-02',
            'nama' => 'EKG',
            'poli_id' => $poli->id,
            'tarif' => '150000',
            'aktif' => '1',
        ])
        ->assertRedirect(route('tindakan.index'));

    $this->assertDatabaseHas('mtr_tindakan', [
        'kode' => 'TND-02',
        'nama' => 'EKG',
        'poli_id' => $poli->id,
        'tarif' => 150000,
    ]);
});

test('kode tindakan wajib, unik, dan tarif tidak boleh negatif', function () {
    Tindakan::create(['kode' => 'TND-01', 'nama' => 'Nebulizer', 'tarif' => 75000]);
    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.tindakan']));

    $this->post(route('tindakan.store'), ['kode' => '', 'nama' => '', 'tarif' => '0'])
        ->assertSessionHasErrors(['kode', 'nama']);

    $this->post(route('tindakan.store'), ['kode' => 'TND-01', 'nama' => 'EKG', 'tarif' => '0'])
        ->assertSessionHasErrors('kode');

    $this->post(route('tindakan.store'), ['kode' => 'TND-03', 'nama' => 'EKG', 'tarif' => '-5'])
        ->assertSessionHasErrors('tarif');
});

test('pengguna dengan hak dapat mengubah tindakan', function () {
    $tindakan = Tindakan::create(['kode' => 'TND-01', 'nama' => 'Nebulizer', 'tarif' => 75000]);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.tindakan']))
        ->put(route('tindakan.update', $tindakan), [
            'kode' => 'TND-01',
            'nama' => 'Nebulizer Anak',
            'tarif' => '90000',
            'aktif' => '1',
        ])
        ->assertRedirect(route('tindakan.index'));

    $this->assertDatabaseHas('mtr_tindakan', [
        'id' => $tindakan->id,
        'nama' => 'Nebulizer Anak',
        'tarif' => 90000,
    ]);
});

test('pengguna dengan hak dapat menghapus tindakan (soft delete)', function () {
    $tindakan = Tindakan::create(['kode' => 'TND-01', 'nama' => 'Nebulizer', 'tarif' => 75000]);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.tindakan']))
        ->delete(route('tindakan.destroy', $tindakan))
        ->assertRedirect(route('tindakan.index'));

    $this->assertSoftDeleted('mtr_tindakan', ['id' => $tindakan->id]);
});
