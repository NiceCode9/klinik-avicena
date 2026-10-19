<?php

use App\Models\Master\KategoriObat;
use App\Models\Master\Obat;
use App\Models\Master\Satuan;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    buatMenu('dasbor', 'dashboard', 'Dasbor');
    buatMenu('master.obat', 'obat.index', 'Obat');
});

function dataObat(Satuan $satuan, array $ganti = []): array
{
    return $ganti + [
        'kode' => 'OBT-001',
        'nama' => 'Paracetamol 500 mg',
        'satuan_id' => $satuan->id,
        'golongan' => 'bebas',
        'stok_minimum' => '20',
        'harga_jual' => '500',
        'aktif' => '1',
    ];
}

test('pengguna dengan hak dapat membuka daftar obat', function () {
    $satuan = Satuan::create(['nama' => 'Tablet']);
    Obat::create(['kode' => 'OBT-001', 'nama' => 'Paracetamol 500 mg', 'satuan_id' => $satuan->id]);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.obat']))
        ->get(route('obat.index'))
        ->assertOk()
        ->assertSee('Paracetamol 500 mg');
});

test('pengguna tanpa hak ditolak membuka daftar obat', function () {
    $this->actingAs(penggunaDenganIzin(['dasbor']))
        ->get(route('obat.index'))
        ->assertForbidden();
});

test('pengguna dengan hak dapat menambah obat', function () {
    $satuan = Satuan::create(['nama' => 'Tablet']);
    $kategori = KategoriObat::create(['nama' => 'Analgesik']);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.obat']))
        ->post(route('obat.store'), dataObat($satuan, [
            'kategori_obat_id' => $kategori->id,
            'golongan' => 'keras',
            'kode_kfa' => '93000001',
        ]))
        ->assertRedirect(route('obat.index'));

    $this->assertDatabaseHas('mtr_obat', [
        'kode' => 'OBT-001',
        'kategori_obat_id' => $kategori->id,
        'satuan_id' => $satuan->id,
        'golongan' => 'keras',
        'harga_jual' => 500,
        'kode_kfa' => '93000001',
    ]);
});

test('kode obat wajib dan unik', function () {
    $satuan = Satuan::create(['nama' => 'Tablet']);
    Obat::create(['kode' => 'OBT-001', 'nama' => 'Paracetamol 500 mg', 'satuan_id' => $satuan->id]);
    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.obat']));

    $this->post(route('obat.store'), dataObat($satuan, ['kode' => '']))
        ->assertSessionHasErrors('kode');

    $this->post(route('obat.store'), dataObat($satuan))
        ->assertSessionHasErrors('kode');
});

test('satuan wajib ada dan golongan harus salah satu pilihan', function () {
    $satuan = Satuan::create(['nama' => 'Tablet']);
    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.obat']));

    $this->post(route('obat.store'), dataObat($satuan, ['satuan_id' => 999999]))
        ->assertSessionHasErrors('satuan_id');

    $this->post(route('obat.store'), dataObat($satuan, ['golongan' => 'terlarang']))
        ->assertSessionHasErrors('golongan');

    $this->post(route('obat.store'), dataObat($satuan, ['kategori_obat_id' => 999999]))
        ->assertSessionHasErrors('kategori_obat_id');
});

test('pengguna dengan hak dapat mengubah obat', function () {
    $satuan = Satuan::create(['nama' => 'Tablet']);
    $obat = Obat::create(['kode' => 'OBT-001', 'nama' => 'Paracetamol 500 mg', 'satuan_id' => $satuan->id]);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.obat']))
        ->put(route('obat.update', $obat), dataObat($satuan, [
            'nama' => 'Paracetamol 650 mg',
            'harga_jual' => '750',
        ]))
        ->assertRedirect(route('obat.index'));

    $this->assertDatabaseHas('mtr_obat', [
        'id' => $obat->id,
        'nama' => 'Paracetamol 650 mg',
        'harga_jual' => 750,
    ]);
});

test('pengguna dengan hak dapat menghapus obat (soft delete)', function () {
    $satuan = Satuan::create(['nama' => 'Tablet']);
    $obat = Obat::create(['kode' => 'OBT-001', 'nama' => 'Paracetamol 500 mg', 'satuan_id' => $satuan->id]);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.obat']))
        ->delete(route('obat.destroy', $obat))
        ->assertRedirect(route('obat.index'));

    $this->assertSoftDeleted('mtr_obat', ['id' => $obat->id]);
});
