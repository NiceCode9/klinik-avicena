<?php

use App\Models\Master\Dokter;
use App\Models\Master\Poli;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    buatMenu('dasbor', 'dashboard', 'Dasbor');
    buatMenu('master.dokter', 'dokter.index', 'Dokter');
});

function dataDokter(array $ganti = []): array
{
    return $ganti + [
        'nama' => 'dr. Andi Pratama',
        'spesialisasi' => 'Umum',
        'no_sip' => '123/SIP/2026',
        'nik' => '3273010101010001',
        'tarif_konsultasi' => '50000',
        'aktif' => '1',
    ];
}

test('pengguna dengan hak dapat membuka daftar dokter', function () {
    Dokter::create(['nama' => 'dr. Budi', 'tarif_konsultasi' => 0]);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.dokter']))
        ->get(route('dokter.index'))
        ->assertOk()
        ->assertSee('dr. Budi');
});

test('pengguna tanpa hak ditolak membuka daftar dokter', function () {
    $this->actingAs(penggunaDenganIzin(['dasbor']))
        ->get(route('dokter.index'))
        ->assertForbidden();
});

test('pengguna dengan hak dapat menambah dokter dengan poli', function () {
    $poli = Poli::create(['kode' => 'INT', 'nama' => 'Poli Umum']);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.dokter']))
        ->post(route('dokter.store'), dataDokter(['poli_id' => $poli->id]))
        ->assertRedirect(route('dokter.index'));

    $this->assertDatabaseHas('mtr_dokter', ['nama' => 'dr. Andi Pratama', 'poli_id' => $poli->id]);
});

test('nik dokter disimpan terenkripsi', function () {
    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.dokter']))
        ->post(route('dokter.store'), dataDokter())
        ->assertRedirect(route('dokter.index'));

    $dokter = Dokter::firstWhere('nama', 'dr. Andi Pratama');
    expect($dokter->nik)->toBe('3273010101010001');

    $mentah = DB::table('mtr_dokter')->where('id', $dokter->id)->value('nik');
    expect($mentah)->not->toBe('3273010101010001');
});

test('nik dokter opsional tetapi harus 16 digit angka', function () {
    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.dokter']));

    $this->post(route('dokter.store'), dataDokter(['nik' => '']))
        ->assertRedirect(route('dokter.index'));
    $this->assertDatabaseHas('mtr_dokter', ['nama' => 'dr. Andi Pratama']);

    $this->post(route('dokter.store'), dataDokter(['nama' => 'dr. Cici', 'nik' => '123']))
        ->assertSessionHasErrors('nik');
});

test('nama dan tarif konsultasi dokter wajib valid', function () {
    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.dokter']));

    $this->post(route('dokter.store'), dataDokter(['nama' => '']))
        ->assertSessionHasErrors('nama');

    $this->post(route('dokter.store'), dataDokter(['tarif_konsultasi' => '-1']))
        ->assertSessionHasErrors('tarif_konsultasi');

    $this->post(route('dokter.store'), dataDokter(['poli_id' => 999999]))
        ->assertSessionHasErrors('poli_id');
});

test('pengguna dengan hak dapat mengubah dokter', function () {
    $dokter = Dokter::create(['nama' => 'dr. Andi', 'tarif_konsultasi' => 0]);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.dokter']))
        ->put(route('dokter.update', $dokter), dataDokter(['nama' => 'dr. Andi Pratama, Sp.PD']))
        ->assertRedirect(route('dokter.index'));

    $this->assertDatabaseHas('mtr_dokter', ['id' => $dokter->id, 'nama' => 'dr. Andi Pratama, Sp.PD']);
});

test('pengguna dengan hak dapat menghapus dokter (soft delete)', function () {
    $dokter = Dokter::create(['nama' => 'dr. Budi', 'tarif_konsultasi' => 0]);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.dokter']))
        ->delete(route('dokter.destroy', $dokter))
        ->assertRedirect(route('dokter.index'));

    $this->assertSoftDeleted('mtr_dokter', ['id' => $dokter->id]);
});
