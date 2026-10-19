<?php

use App\Models\Master\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    buatMenu('dasbor', 'dashboard', 'Dasbor');
    buatMenu('master.supplier', 'supplier.index', 'Supplier');
});

test('pengguna dengan hak dapat membuka daftar supplier', function () {
    Supplier::create(['nama' => 'PT Sehat Sentosa']);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.supplier']))
        ->get(route('supplier.index'))
        ->assertOk()
        ->assertSee('PT Sehat Sentosa');
});

test('pengguna tanpa hak ditolak membuka daftar supplier', function () {
    $this->actingAs(penggunaDenganIzin(['dasbor']))
        ->get(route('supplier.index'))
        ->assertForbidden();
});

test('pengguna dengan hak dapat menambah supplier', function () {
    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.supplier']))
        ->post(route('supplier.store'), [
            'nama' => 'PT Sehat Sentosa',
            'alamat' => 'Jl. Merdeka No. 1',
            'no_telepon' => '021123456',
            'nama_kontak' => 'Budi',
            'no_izin_pbf' => 'PBF-001',
            'aktif' => '1',
        ])
        ->assertRedirect(route('supplier.index'));

    $this->assertDatabaseHas('mtr_supplier', [
        'nama' => 'PT Sehat Sentosa',
        'nama_kontak' => 'Budi',
        'no_izin_pbf' => 'PBF-001',
        'aktif' => 1,
    ]);
});

test('nama supplier wajib diisi', function () {
    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.supplier']))
        ->post(route('supplier.store'), ['nama' => ''])
        ->assertSessionHasErrors('nama');
});

test('pengguna dengan hak dapat mengubah supplier', function () {
    $supplier = Supplier::create(['nama' => 'PT Sehat Sentosa']);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.supplier']))
        ->put(route('supplier.update', $supplier), [
            'nama' => 'PT Sehat Sentosa Jaya',
            'no_telepon' => '021999888',
            'aktif' => '1',
        ])
        ->assertRedirect(route('supplier.index'));

    $this->assertDatabaseHas('mtr_supplier', [
        'id' => $supplier->id,
        'nama' => 'PT Sehat Sentosa Jaya',
        'no_telepon' => '021999888',
    ]);
});

test('pengguna dengan hak dapat menghapus supplier (soft delete)', function () {
    $supplier = Supplier::create(['nama' => 'PT Sehat Sentosa']);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.supplier']))
        ->delete(route('supplier.destroy', $supplier))
        ->assertRedirect(route('supplier.index'));

    $this->assertSoftDeleted('mtr_supplier', ['id' => $supplier->id]);
});
