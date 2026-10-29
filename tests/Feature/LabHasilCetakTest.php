<?php

use App\Models\Lab\Hasil;
use App\Models\Lab\Permintaan;
use App\Models\Lab\PermintaanDetail;
use App\Models\Master\Dokter;
use App\Models\Master\ParameterLab;
use App\Models\Master\Pasien;
use App\Models\Master\PemeriksaanLab;
use App\Models\Master\Poli;
use App\Models\Registrasi\Kunjungan;
use App\Models\User;
use Database\Seeders\DataAwalSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

test('cetak belum dikunci -> 403', function () {
    $this->seed(DataAwalSeeder::class);
    $pasien = Pasien::factory()->create();
    $kunjungan = Kunjungan::factory()->create(['pasien_id' => $pasien->id]);
    $permintaan = Permintaan::factory()->create(['kunjungan_id' => $kunjungan->id, 'dikunci' => false]);
    $user = User::where('username', 'admin')->first();

    $this->actingAs($user)->get(route('lab-hasil.cetak', $permintaan))->assertStatus(403);
});

test('cetak terkunci -> 200 dan data', function () {
    $this->seed(DataAwalSeeder::class);
    $pasien = Pasien::factory()->create(['nama_lengkap' => 'Pasien Cetak']);
    $poliLab = Poli::where('kode', 'LAB')->first();
    $dokter = Dokter::factory()->create(['nama' => 'Dr. Lab', 'poli_id' => $poliLab->id, 'aktif' => true]);
    $kunjungan = Kunjungan::factory()->create(['pasien_id' => $pasien->id]);
    $permintaan = Permintaan::factory()->create([
        'kunjungan_id' => $kunjungan->id,
        'dikunci' => true,
        'dikunci_pada' => now(),
        'dokter_id' => $dokter->id,
    ]);

    $lab = PemeriksaanLab::create(['kode' => 'LABX', 'nama' => 'Gula', 'tarif' => 10000, 'aktif' => true]);
    $param = ParameterLab::create(['pemeriksaan_lab_id' => $lab->id, 'nama_parameter' => 'Glukosa', 'satuan' => 'mg/dL', 'nilai_rujukan_min' => 70, 'nilai_rujukan_max' => 100, 'urutan' => 0]);
    $detail = PermintaanDetail::create(['permintaan_id' => $permintaan->id, 'pemeriksaan_lab_id' => $lab->id, 'tarif' => 10000]);
    Hasil::create(['permintaan_detail_id' => $detail->id, 'parameter_lab_id' => $param->id, 'nilai' => '85', 'satuan' => 'mg/dL', 'nilai_rujukan' => '70 - 100', 'penanda' => 'normal', 'diinput_oleh' => null]);

    $user = User::where('username', 'admin')->first();
    $this->actingAs($user)->get(route('lab-hasil.cetak', $permintaan))
        ->assertStatus(200)
        ->assertSee('Pasien Cetak')
        ->assertSee('Dr. Lab')
        ->assertSee('Glukosa')
        ->assertSee('85');
});
