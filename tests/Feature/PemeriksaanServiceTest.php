<?php

use App\Events\PemeriksaanDimulai;
use App\Events\PemeriksaanSelesai;
use App\Models\Erm\Pemeriksaan;
use App\Models\Master\Dokter;
use App\Models\Master\Icd10;
use App\Models\Master\Pasien;
use App\Models\Master\Poli;
use App\Models\User;
use App\Services\Erm\Exceptions\DataTerkunciException;
use App\Services\Erm\PemeriksaanService;
use App\Services\Registrasi\AntreanService;
use App\Services\Registrasi\KunjunganService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

function setupKunjunganMenunggu(): array
{
    $poli = Poli::factory()->create(['kode' => 'INT', 'nama' => 'Poli Penyakit Dalam', 'aktif' => true]);
    $pasien = Pasien::factory()->create();
    $dokter = Dokter::factory()->create(['poli_id' => $poli->id]);
    $user = User::factory()->create();
    $kunjungan = app(KunjunganService::class)->buatKunjunganPoli($pasien, $user);
    $antrean = app(AntreanService::class)->ambilNomor($poli, $kunjungan);
    app(KunjunganService::class)->simpanTtv($kunjungan, $poli, [
        'tekanan_darah_sistolik' => 120,
        'tekanan_darah_diastolik' => 80,
        'nadi' => 80,
        'suhu' => 36.5,
        'berat_badan' => 60.5,
    ], $user);
    $kunjungan->refresh();
    $antrean->refresh();

    return [$kunjungan, $antrean, $dokter, $user, $poli];
}

test('mulai mengubah status kunjungan ke diperiksa dan antrean ke dilayani', function () {
    [$kunjungan, $antrean, $dokter, $user] = setupKunjunganMenunggu();

    Event::fake([PemeriksaanDimulai::class]);

    $pemeriksaan = app(PemeriksaanService::class)->mulai($antrean, $dokter, $user);

    expect($pemeriksaan)->toBeInstanceOf(Pemeriksaan::class);
    $kunjungan->refresh();
    $antrean->refresh();
    expect($kunjungan->status)->toBe('diperiksa')
        ->and($kunjungan->waktu_mulai)->not->toBeNull()
        ->and($antrean->status)->toBe('dilayani');

    Event::assertDispatched(PemeriksaanDimulai::class);
});

test('mulai dari Kunjungan juga berfungsi', function () {
    [$kunjungan, $antrean, $dokter, $user] = setupKunjunganMenunggu();

    $pemeriksaan = app(PemeriksaanService::class)->mulai($kunjungan, $dokter, $user);

    expect($pemeriksaan->kunjungan_id)->toBe($kunjungan->id);
    $kunjungan->refresh();
    expect($kunjungan->status)->toBe('diperiksa');
});

test('simpanDraf menyimpan SOAP dan diagnosis', function () {
    [$kunjungan, $antrean, $dokter, $user] = setupKunjunganMenunggu();
    $icd10 = Icd10::create(['kode' => 'A00', 'nama_diagnosis' => 'Kolera']);
    $pemeriksaan = app(PemeriksaanService::class)->mulai($antrean, $dokter, $user);

    $updated = app(PemeriksaanService::class)->simpanDraf($pemeriksaan, [
        'subjektif' => 'Keluhan',
        'objektif' => 'Obj',
        'asesmen' => 'Asesmen',
        'rencana' => 'Rencana',
        'diagnosis' => [['icd10_id' => $icd10->id, 'primer' => false]],
    ], $user);

    expect($updated->subjektif)->toBe('Keluhan')
        ->and($updated->diagnosis()->count())->toBe(1);
});

test('simpanFinal tanpa diagnosis primer ditolak', function () {
    [$kunjungan, $antrean, $dokter, $user] = setupKunjunganMenunggu();
    $icd10 = Icd10::create(['kode' => 'A00', 'nama_diagnosis' => 'Kolera']);
    $pemeriksaan = app(PemeriksaanService::class)->mulai($antrean, $dokter, $user);

    expect(fn () => app(PemeriksaanService::class)->simpanFinal($pemeriksaan, [
        'diagnosis' => [['icd10_id' => $icd10->id, 'primer' => false]],
        'kode_cara_keluar' => '1',
        'nama_cara_keluar' => 'Pulang',
    ], $user))->toThrow(InvalidArgumentException::class);
});

test('simpanFinal tanpa cara keluar ditolak', function () {
    [$kunjungan, $antrean, $dokter, $user] = setupKunjunganMenunggu();
    $icd10 = Icd10::create(['kode' => 'A00', 'nama_diagnosis' => 'Kolera']);
    $pemeriksaan = app(PemeriksaanService::class)->mulai($antrean, $dokter, $user);

    expect(fn () => app(PemeriksaanService::class)->simpanFinal($pemeriksaan, [
        'diagnosis' => [['icd10_id' => $icd10->id, 'primer' => true]],
    ], $user))->toThrow(InvalidArgumentException::class);
});

test('simpanFinal mengisi waktu_selesai dan memancarkan event setelah commit', function () {
    [$kunjungan, $antrean, $dokter, $user] = setupKunjunganMenunggu();
    $icd10 = Icd10::create(['kode' => 'A00', 'nama_diagnosis' => 'Kolera']);
    $pemeriksaan = app(PemeriksaanService::class)->mulai($antrean, $dokter, $user);

    Event::fake([PemeriksaanSelesai::class]);

    $final = app(PemeriksaanService::class)->simpanFinal($pemeriksaan, [
        'diagnosis' => [['icd10_id' => $icd10->id, 'primer' => true]],
        'kode_cara_keluar' => '1',
        'nama_cara_keluar' => 'Pulang',
    ], $user);

    $kunjungan->refresh();
    expect($kunjungan->waktu_selesai)->not->toBeNull()
        ->and($kunjungan->kode_cara_keluar)->toBe('1');

    Event::assertDispatched(PemeriksaanSelesai::class);
});

test('simpanFinal tanpa lab/resep menggerakkan kunjungan ke kasir', function () {
    [$kunjungan, $antrean, $dokter, $user] = setupKunjunganMenunggu();
    $icd10 = Icd10::create(['kode' => 'A00', 'nama_diagnosis' => 'Kolera']);
    $pemeriksaan = app(PemeriksaanService::class)->mulai($antrean, $dokter, $user);

    app(PemeriksaanService::class)->simpanFinal($pemeriksaan, [
        'diagnosis' => [['icd10_id' => $icd10->id, 'primer' => true]],
        'kode_cara_keluar' => '1',
        'nama_cara_keluar' => 'Pulang',
    ], $user);

    $kunjungan->refresh();
    expect($kunjungan->status)->toBe('kasir');
});

test('pemeriksaan terkunci menolak ubah', function () {
    [$kunjungan, $antrean, $dokter, $user] = setupKunjunganMenunggu();
    $pemeriksaan = app(PemeriksaanService::class)->mulai($antrean, $dokter, $user);
    $pemeriksaan->update(['dikunci' => true, 'dikunci_pada' => now()]);

    expect(fn () => app(PemeriksaanService::class)->simpanDraf($pemeriksaan, ['subjektif' => 'x'], $user))
        ->toThrow(DataTerkunciException::class);
});

test('event tidak terpancar jika transaksi gagal', function () {
    [$kunjungan, $antrean, $dokter, $user] = setupKunjunganMenunggu();
    $icd10 = Icd10::create(['kode' => 'A00', 'nama_diagnosis' => 'Kolera']);
    $pemeriksaan = app(PemeriksaanService::class)->mulai($antrean, $dokter, $user);

    Event::fake([PemeriksaanSelesai::class]);

    // force failure: invalid icd10_id (non-existent) should fail FK
    try {
        app(PemeriksaanService::class)->simpanFinal($pemeriksaan, [
            'diagnosis' => [['icd10_id' => 999999, 'primer' => true]],
            'kode_cara_keluar' => '1',
            'nama_cara_keluar' => 'Pulang',
        ], $user);
    } catch (Throwable $e) {
        // expected
    }

    Event::assertNotDispatched(PemeriksaanSelesai::class);
});
