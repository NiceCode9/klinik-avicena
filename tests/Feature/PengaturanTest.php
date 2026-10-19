<?php

use App\Models\Sys\Pengaturan;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    buatMenu('dasbor', 'dashboard', 'Dasbor');
    buatMenu('sistem.pengaturan', 'pengaturan.index', 'Pengaturan');

    Pengaturan::create([
        'kunci' => 'biaya_jasa_racik',
        'nilai' => '0',
        'keterangan' => 'Biaya flat jasa racik per resep racikan (Rp)',
    ]);
    Pengaturan::create([
        'kunci' => 'retur_pasien_diizinkan',
        'nilai' => '1',
        'keterangan' => '1 = retur obat dari pasien diizinkan',
    ]);
    Pengaturan::create([
        'kunci' => 'batas_peringatan_kedaluwarsa_hari',
        'nilai' => '90',
        'keterangan' => 'Peringatan obat mendekati kedaluwarsa',
    ]);
});

test('pengguna dengan hak dapat membuka halaman pengaturan', function () {
    $this->actingAs(penggunaDenganIzin(['dasbor', 'sistem.pengaturan']))
        ->get(route('pengaturan.index'))
        ->assertOk()
        ->assertSee('biaya_jasa_racik')
        ->assertSee('Biaya flat jasa racik per resep racikan (Rp)')
        ->assertSee('Simpan');
});

test('pengguna tanpa hak ditolak membuka halaman pengaturan', function () {
    $this->actingAs(penggunaDenganIzin(['dasbor']))
        ->get(route('pengaturan.index'))
        ->assertForbidden();
});

test('pengguna dengan hanya hak lihat tidak boleh menyimpan pengaturan', function () {
    $pengguna = penggunaHanyaLihat('sistem.pengaturan');

    $this->actingAs($pengguna)
        ->get(route('pengaturan.index'))
        ->assertOk()
        ->assertDontSee('Simpan');

    $this->actingAs($pengguna)
        ->put(route('pengaturan.update'), ['nilai' => ['biaya_jasa_racik' => '5000']])
        ->assertForbidden();

    $this->assertDatabaseHas('sys_pengaturan', ['kunci' => 'biaya_jasa_racik', 'nilai' => '0']);
});

test('menyimpan mengubah nilai pengaturan', function () {
    $this->actingAs(penggunaDenganIzin(['dasbor', 'sistem.pengaturan']))
        ->put(route('pengaturan.update'), [
            'nilai' => [
                'biaya_jasa_racik' => '5000',
                'retur_pasien_diizinkan' => '0',
                'batas_peringatan_kedaluwarsa_hari' => '30',
            ],
        ])
        ->assertRedirect(route('pengaturan.index'))
        ->assertSessionHas('sukses');

    $this->assertDatabaseHas('sys_pengaturan', ['kunci' => 'biaya_jasa_racik', 'nilai' => '5000']);
    $this->assertDatabaseHas('sys_pengaturan', ['kunci' => 'retur_pasien_diizinkan', 'nilai' => '0']);
    $this->assertDatabaseHas('sys_pengaturan', ['kunci' => 'batas_peringatan_kedaluwarsa_hari', 'nilai' => '30']);
});

test('biaya jasa racik harus angka dan tidak negatif', function () {
    $this->actingAs(penggunaDenganIzin(['dasbor', 'sistem.pengaturan']));

    $this->put(route('pengaturan.update'), ['nilai' => ['biaya_jasa_racik' => 'seribu']])
        ->assertSessionHasErrors('nilai.biaya_jasa_racik');

    $this->put(route('pengaturan.update'), ['nilai' => ['biaya_jasa_racik' => '-1']])
        ->assertSessionHasErrors('nilai.biaya_jasa_racik');

    $this->assertDatabaseHas('sys_pengaturan', ['kunci' => 'biaya_jasa_racik', 'nilai' => '0']);
});

test('batas peringatan kedaluwarsa harus bilangan bulat tidak negatif', function () {
    $this->actingAs(penggunaDenganIzin(['dasbor', 'sistem.pengaturan']));

    $this->put(route('pengaturan.update'), ['nilai' => ['batas_peringatan_kedaluwarsa_hari' => '30.5']])
        ->assertSessionHasErrors('nilai.batas_peringatan_kedaluwarsa_hari');

    $this->put(route('pengaturan.update'), ['nilai' => ['batas_peringatan_kedaluwarsa_hari' => '-1']])
        ->assertSessionHasErrors('nilai.batas_peringatan_kedaluwarsa_hari');
});

test('kunci yang tidak terdaftar di database diabaikan', function () {
    $this->actingAs(penggunaDenganIzin(['dasbor', 'sistem.pengaturan']))
        ->put(route('pengaturan.update'), [
            'nilai' => [
                'biaya_jasa_racik' => '2500',
                'kunci_liar' => 'x',
            ],
        ])
        ->assertRedirect(route('pengaturan.index'));

    $this->assertDatabaseHas('sys_pengaturan', ['kunci' => 'biaya_jasa_racik', 'nilai' => '2500']);
    $this->assertDatabaseMissing('sys_pengaturan', ['kunci' => 'kunci_liar']);
});

test('kunci yang belum dikenal kode tampil sebagai teks dan tetap bisa diubah', function () {
    Pengaturan::create(['kunci' => 'kunci_baru', 'nilai' => 'sebelumnya']);

    $pengguna = penggunaDenganIzin(['dasbor', 'sistem.pengaturan']);

    $this->actingAs($pengguna)
        ->get(route('pengaturan.index'))
        ->assertOk()
        ->assertSee('kunci_baru');

    $this->actingAs($pengguna)
        ->put(route('pengaturan.update'), ['nilai' => ['kunci_baru' => 'sesudahnya']])
        ->assertRedirect(route('pengaturan.index'));

    $this->assertDatabaseHas('sys_pengaturan', ['kunci' => 'kunci_baru', 'nilai' => 'sesudahnya']);
});
