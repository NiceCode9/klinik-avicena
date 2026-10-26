<?php

use App\Models\Erm\Tindakan as ErmTindakan;
use App\Models\Master\Dokter;
use App\Models\Master\Pasien;
use App\Models\Master\Poli;
use App\Models\Master\Tindakan as MasterTindakan;
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

function setupPemeriksaanInt(): array
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

    return [$pemeriksaan, $user, $poli];
}

test('tarif disnapshot dari master saat input tindakan', function () {
    [$pemeriksaan, $user, $poli] = setupPemeriksaanInt();
    $master = MasterTindakan::create(['kode' => 'T01', 'nama' => 'Konsultasi', 'poli_id' => $poli->id, 'tarif' => 50000, 'aktif' => true]);

    app(PemeriksaanService::class)->simpanTindakan($pemeriksaan, [
        ['tindakan_id' => $master->id, 'jumlah' => 2, 'catatan' => null],
    ], $user);

    $tindakan = ErmTindakan::where('pemeriksaan_id', $pemeriksaan->id)->first();
    expect((float) $tindakan->tarif)->toBe(50000.0)
        ->and($tindakan->jumlah)->toBe(2);
});

test('ubah master tarif tidak mempengaruhi tindakan lama', function () {
    [$pemeriksaan, $user, $poli] = setupPemeriksaanInt();
    $master = MasterTindakan::create(['kode' => 'T02', 'nama' => 'Tindakan X', 'poli_id' => $poli->id, 'tarif' => 30000, 'aktif' => true]);

    app(PemeriksaanService::class)->simpanTindakan($pemeriksaan, [
        ['tindakan_id' => $master->id, 'jumlah' => 1, 'catatan' => null],
    ], $user);

    $master->update(['tarif' => 99999]);

    $tindakan = ErmTindakan::where('pemeriksaan_id', $pemeriksaan->id)->first();
    expect((float) $tindakan->tarif)->toBe(30000.0);
});

test('terkunci menolak simpan tindakan', function () {
    [$pemeriksaan, $user, $poli] = setupPemeriksaanInt();
    $pemeriksaan->update(['dikunci' => true, 'dikunci_pada' => now()]);

    expect(fn () => app(PemeriksaanService::class)->simpanTindakan($pemeriksaan, [], $user))
        ->toThrow(DataTerkunciException::class);
});

test('tindakan difilter per poli (poli lain ditolak)', function () {
    [$pemeriksaan, $user, $poli] = setupPemeriksaanInt();
    $poliLain = Poli::factory()->create(['kode' => 'GIG', 'nama' => 'Poli Gigi', 'aktif' => true]);
    $masterLain = MasterTindakan::create(['kode' => 'T03', 'nama' => 'Tindakan Gigi', 'poli_id' => $poliLain->id, 'tarif' => 10000, 'aktif' => true]);

    expect(fn () => app(PemeriksaanService::class)->simpanTindakan($pemeriksaan, [
        ['tindakan_id' => $masterLain->id, 'jumlah' => 1, 'catatan' => null],
    ], $user))->toThrow(InvalidArgumentException::class);
});
