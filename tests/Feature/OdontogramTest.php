<?php

use App\Models\Erm\Odontogram;
use App\Models\Erm\Pemeriksaan;
use App\Models\Master\Dokter;
use App\Models\Master\Pasien;
use App\Models\Master\Poli;
use App\Models\User;
use App\Services\Erm\Exceptions\DataTerkunciException;
use App\Services\Erm\PemeriksaanService;
use App\Services\Registrasi\AntreanService;
use App\Services\Registrasi\KunjunganService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

function setupPemeriksaanGigi(): array
{
    $poli = Poli::factory()->create(['kode' => 'GIG', 'nama' => 'Poli Gigi', 'aktif' => true]);
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
    app(AntreanService::class)->panggil($antrean);
    $antrean->refresh();
    $pemeriksaan = app(PemeriksaanService::class)->mulai($antrean, $dokter, $user);

    return [$pemeriksaan, $user];
}

test('odontogram tersimpan dengan replace-all', function () {
    [$pemeriksaan, $user] = setupPemeriksaanGigi();

    app(PemeriksaanService::class)->simpanOdontogram($pemeriksaan, [
        'items' => [
            ['nomor_gigi' => '11', 'permukaan' => ['oklusal'], 'kondisi' => 'karies', 'tindakan' => 'tambal', 'catatan' => null],
            ['nomor_gigi' => '12', 'permukaan' => ['mesial'], 'kondisi' => 'tambalan', 'tindakan' => null, 'catatan' => null],
        ],
    ], $user);

    expect(Odontogram::where('pemeriksaan_id', $pemeriksaan->id)->count())->toBe(2);

    // replace-all: simpan ulang 1 item
    app(PemeriksaanService::class)->simpanOdontogram($pemeriksaan, [
        'items' => [
            ['nomor_gigi' => '11', 'permukaan' => ['oklusal'], 'kondisi' => 'karies', 'tindakan' => null, 'catatan' => null],
        ],
    ], $user);

    expect(Odontogram::where('pemeriksaan_id', $pemeriksaan->id)->count())->toBe(1);
});

test('nomor gigi FDI tidak valid ditolak', function () {
    [$pemeriksaan, $user] = setupPemeriksaanGigi();

    expect(fn () => app(PemeriksaanService::class)->simpanOdontogram($pemeriksaan, [
        'items' => [['nomor_gigi' => '99', 'permukaan' => [], 'kondisi' => 'karies']],
    ], $user))->toThrow(InvalidArgumentException::class);
});

test('permukaan tidak valid ditolak', function () {
    [$pemeriksaan, $user] = setupPemeriksaanGigi();

    expect(fn () => app(PemeriksaanService::class)->simpanOdontogram($pemeriksaan, [
        'items' => [['nomor_gigi' => '11', 'permukaan' => ['foo'], 'kondisi' => 'karies']],
    ], $user))->toThrow(InvalidArgumentException::class);
});

test('terkunci menolak simpan odontogram', function () {
    [$pemeriksaan, $user] = setupPemeriksaanGigi();
    $pemeriksaan->update(['dikunci' => true, 'dikunci_pada' => now()]);

    expect(fn () => app(PemeriksaanService::class)->simpanOdontogram($pemeriksaan, [
        'items' => [['nomor_gigi' => '11', 'permukaan' => [], 'kondisi' => 'karies']],
    ], $user))->toThrow(DataTerkunciException::class);
});

test('hanya poli GIG yang boleh', function () {
    // setup INT poli pemeriksaan
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
    app(AntreanService::class)->panggil($antrean);
    $antrean->refresh();
    $pemeriksaan = app(PemeriksaanService::class)->mulai($antrean, $dokter, $user);

    expect(fn () => app(PemeriksaanService::class)->simpanOdontogram($pemeriksaan, [
        'items' => [['nomor_gigi' => '11', 'permukaan' => [], 'kondisi' => 'karies']],
    ], $user))->toThrow(InvalidArgumentException::class);
});
