<?php

use App\Events\LabDiminta;
use App\Models\Master\Dokter;
use App\Models\Master\Pasien;
use App\Models\Master\PemeriksaanLab;
use App\Models\Master\ParameterLab;
use App\Models\Master\Poli;
use App\Models\User;
use App\Services\Lab\LabMandiriService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

test('daftarkan mandiri membuat kunjungan lab_mandiri, antrean LAB, permintaan mandiri dan detail', function () {
    $poliLab = Poli::factory()->create(['kode' => 'LAB', 'nama' => 'Poli Lab', 'aktif' => true]);
    $pasien = Pasien::factory()->create();
    $dokter = Dokter::factory()->create(['poli_id' => $poliLab->id, 'aktif' => true]);
    $user = User::factory()->create();

    $lab = PemeriksaanLab::create(['kode' => 'LAB01', 'nama' => 'Gula Darah', 'tarif' => 15000, 'aktif' => true]);

    Event::fake([LabDiminta::class]);

    $permintaan = app(LabMandiriService::class)->daftarkan($user, [
        'pasien_id' => $pasien->id,
        'dokter_id' => $dokter->id,
        'pemeriksaan_lab_ids' => [$lab->id],
    ]);

    expect($permintaan->sumber)->toBe('mandiri')
        ->and($permintaan->rujukan_lab_id)->toBeNull()
        ->and($permintaan->dokter_id)->toBe($dokter->id)
        ->and($permintaan->permintaanDetail()->count())->toBe(1);

    $antreanLab = \App\Models\Registrasi\Antrean::where('kunjungan_id', $permintaan->kunjungan_id)
        ->whereHas('poli', fn ($q) => $q->where('kode', 'LAB'))->first();
    expect($antreanLab)->not->toBeNull()
        ->and($antreanLab->nomor_antrean)->toMatch('/^LAB-\d{3}$/');

    $permintaan->kunjungan->refresh();
    expect($permintaan->kunjungan->jenis_kunjungan)->toBe('lab_mandiri')
        ->and($permintaan->kunjungan->status)->toBe('lab');

    Event::assertDispatched(LabDiminta::class);
});

test('daftarkan without dokter ditolak', function () {
    $pasien = Pasien::factory()->create();
    $user = User::factory()->create();
    $lab = PemeriksaanLab::create(['kode' => 'LAB01', 'nama' => 'Gula Darah', 'tarif' => 15000, 'aktif' => true]);

    expect(fn () => app(LabMandiriService::class)->daftarkan($user, [
        'pasien_id' => $pasien->id,
        'pemeriksaan_lab_ids' => [$lab->id],
    ]))->toThrow(\InvalidArgumentException::class);
});

test('pendaftaran pasien baru terpakai (cari NIK)', function () {
    $poliLab = Poli::factory()->create(['kode' => 'LAB', 'nama' => 'Poli Lab', 'aktif' => true]);
    $dokter = Dokter::factory()->create(['poli_id' => $poliLab->id, 'aktif' => true]);
    $user = User::factory()->create();
    $lab = PemeriksaanLab::create(['kode' => 'LAB01', 'nama' => 'Gula Darah', 'tarif' => 15000, 'aktif' => true]);

    $nik = '1234567890123456';
    $permintaan = app(LabMandiriService::class)->daftarkan($user, [
        'nik' => $nik,
        'nama_lengkap' => 'Budi',
        'tanggal_lahir' => '1990-01-01',
        'jenis_kelamin' => 'L',
        'dokter_id' => $dokter->id,
        'pemeriksaan_lab_ids' => [$lab->id],
    ]);

    expect(\App\Models\Master\Pasien::where('nik_hash', \App\Services\NikHasher::hash($nik))->exists())->toBeTrue();

    // second time same nik reuses patient
    $permintaan2 = app(LabMandiriService::class)->daftarkan($user, [
        'nik' => $nik,
        'dokter_id' => $dokter->id,
        'pemeriksaan_lab_ids' => [$lab->id],
    ]);
    expect($permintaan2->kunjungan->pasien_id)->toBe($permintaan->kunjungan->pasien_id);
});
