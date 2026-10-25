<?php

use App\Models\Erm\DetailPenyakitDalam;
use App\Models\Erm\DetailSaraf;
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

function setupPemeriksaan(): array
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
    app(AntreanService::class)->panggil($antrean);
    $antrean->refresh();
    $pemeriksaan = app(PemeriksaanService::class)->mulai($antrean, $dokter, $user);

    return [$pemeriksaan, $user];
}

test('simpan detail penyakit dalam tersimpan dan terbaca ulang', function () {
    [$pemeriksaan, $user] = setupPemeriksaan();

    $detail = app(PemeriksaanService::class)->simpanDetailPenyakitDalam($pemeriksaan, [
        'riwayat_penyakit_kronis' => 'DM 5 tahun',
        'status_metabolik' => 'terkontrol',
        'review_sistem_organ' => ['kardiovaskular' => 'sesak', 'respirasi' => 'normal'],
    ], $user);

    $baca = DetailPenyakitDalam::where('pemeriksaan_id', $pemeriksaan->id)->first();
    expect($baca->riwayat_penyakit_kronis)->toBe('DM 5 tahun')
        ->and($baca->review_sistem_organ['kardiovaskular'])->toBe('sesak');
});

test('validasi struktur JSON menolak kunci tidak diizinkan', function () {
    [$pemeriksaan, $user] = setupPemeriksaan();

    expect(fn () => app(PemeriksaanService::class)->simpanDetailPenyakitDalam($pemeriksaan, [
        'review_sistem_organ' => ['foo' => 'bar'],
    ], $user))->toThrow(InvalidArgumentException::class);
});

test('simpan detail saraf tersimpan dan terbaca ulang', function () {
    [$pemeriksaan, $user] = setupPemeriksaan();

    app(PemeriksaanService::class)->simpanDetailSaraf($pemeriksaan, [
        'evaluasi_motorik' => ['kekuatan_otot' => '5/5', 'tonus' => 'normal'],
        'saraf_kranial' => ['VII' => 'parese kiri'],
    ], $user);

    $baca = DetailSaraf::where('pemeriksaan_id', $pemeriksaan->id)->first();
    expect($baca->evaluasi_motorik['kekuatan_otot'])->toBe('5/5')
        ->and($baca->saraf_kranial['VII'])->toBe('parese kiri');
});

test('terkunci menolak simpan detail', function () {
    [$pemeriksaan, $user] = setupPemeriksaan();
    $pemeriksaan->update(['dikunci' => true, 'dikunci_pada' => now()]);

    expect(fn () => app(PemeriksaanService::class)->simpanDetailPenyakitDalam($pemeriksaan, [
        'riwayat_penyakit_kronis' => 'x',
    ], $user))->toThrow(DataTerkunciException::class);
});
