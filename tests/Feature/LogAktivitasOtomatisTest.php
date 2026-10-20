<?php

use App\Models\Master\Dokter;
use App\Models\Master\Satuan;
use App\Models\Sys\LogAktivitas;
use App\Models\Sys\Pengaturan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    buatMenu('dasbor', 'dashboard', 'Dasbor');
    buatMenu('sistem.pengaturan', 'pengaturan.index', 'Pengaturan');
});

test('menambah, mengubah, dan menghapus data master tercatat otomatis', function () {
    $pengguna = User::factory()->create();
    $this->actingAs($pengguna);

    $satuan = Satuan::create(['nama' => 'Tablet']);
    $this->assertDatabaseHas('sys_log_aktivitas', [
        'pengguna_id' => $pengguna->id,
        'aksi' => 'tambah',
        'nama_tabel' => 'mtr_satuan',
        'data_id' => $satuan->id,
    ]);

    $satuan->update(['nama' => 'Kapsul']);

    $ubah = LogAktivitas::where('aksi', 'ubah')
        ->where('nama_tabel', 'mtr_satuan')
        ->where('data_id', $satuan->id)
        ->sole();

    expect($ubah->nilai_lama)->toBeNull()
        ->and($ubah->nilai_baru['kolom_berubah'])->toContain('nama')
        ->and($ubah->nilai_baru['kolom_berubah'])->not->toContain('updated_at');

    $satuan->delete();

    $this->assertDatabaseHas('sys_log_aktivitas', [
        'aksi' => 'hapus',
        'nama_tabel' => 'mtr_satuan',
        'data_id' => $satuan->id,
    ]);
});

test('nilai sensitif tidak ikut tercatat', function () {
    $this->actingAs(User::factory()->create());

    $dokter = Dokter::create(['nama' => 'dr. Uji', 'nik' => '3201234567890123']);
    $dokter->update(['nik' => '3201999999999999']);

    $isi = LogAktivitas::where('nama_tabel', 'mtr_dokter')->get()->toJson();

    expect($isi)->not->toContain('3201234567890123')
        ->not->toContain('3201999999999999');
});

test('tanpa sesi login tetap tercatat dengan pengguna kosong', function () {
    $satuan = Satuan::create(['nama' => 'Tablet']);

    $this->assertDatabaseHas('sys_log_aktivitas', [
        'pengguna_id' => null,
        'aksi' => 'tambah',
        'nama_tabel' => 'mtr_satuan',
        'data_id' => $satuan->id,
    ]);
});

test('menyimpan pengaturan tercatat', function () {
    Pengaturan::create(['kunci' => 'biaya_jasa_racik', 'nilai' => '0']);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'sistem.pengaturan']))
        ->put(route('pengaturan.update'), ['nilai' => ['biaya_jasa_racik' => '5000']])
        ->assertRedirect(route('pengaturan.index'));

    $this->assertDatabaseHas('sys_log_aktivitas', [
        'aksi' => 'ubah',
        'nama_tabel' => 'sys_pengaturan',
    ]);
});

test('ganti kata sandi mencatat satu baris log tanpa nilai sandi', function () {
    $pengguna = User::factory()->create(['password' => Hash::make('sandi-lama')]);
    $this->actingAs($pengguna);

    $this->put('/ganti-kata-sandi', [
        'kata_sandi_lama' => 'sandi-lama',
        'kata_sandi' => 'sandi-baru-123',
        'kata_sandi_confirmation' => 'sandi-baru-123',
    ])->assertRedirect();

    $log = LogAktivitas::where('pengguna_id', $pengguna->id)
        ->where('aksi', 'ubah')
        ->where('nama_tabel', 'sys_pengguna')
        ->get();

    expect($log)->toHaveCount(1)
        ->and($log->first()->nilai_baru['kolom_berubah'])->toContain('password')
        ->and($log->first()->toJson())->not->toContain('sandi-baru-123');
});
