<?php

use App\Events\LabDiminta;
use App\Models\Erm\RujukanLab;
use App\Models\Lab\Permintaan;
use App\Models\Master\Dokter;
use App\Models\Master\Pasien;
use App\Models\Master\PemeriksaanLab;
use App\Models\Master\Poli;
use App\Models\Registrasi\Antrean;
use App\Models\User;
use App\Services\Erm\PemeriksaanService;
use App\Services\Erm\RujukanLabService;
use App\Services\Registrasi\AntreanService;
use App\Services\Registrasi\KunjunganService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

function setupPemeriksaanForRujukan(): array
{
    $poli = Poli::factory()->create(['kode' => 'INT', 'nama' => 'Poli Penyakit Dalam', 'aktif' => true]);
    $poliLab = Poli::factory()->create(['kode' => 'LAB', 'nama' => 'Poli Lab', 'aktif' => true]);
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

    return [$pemeriksaan, $user, $poli, $poliLab];
}

test('buat rujukan lab membuat rujukan, permintaan, detail, antrean LAB dan status lab', function () {
    [$pemeriksaan, $user] = setupPemeriksaanForRujukan();
    $lab1 = PemeriksaanLab::create(['kode' => 'LAB01', 'nama' => 'Darah Lengkap', 'tarif' => 25000, 'aktif' => true]);
    $lab2 = PemeriksaanLab::create(['kode' => 'LAB02', 'nama' => 'Gula Darah', 'tarif' => 15000, 'aktif' => true]);

    Event::fake([LabDiminta::class]);

    $rujukan = app(RujukanLabService::class)->buat($pemeriksaan, [$lab1->id, $lab2->id], 'catatan', $user);

    expect($rujukan)->toBeInstanceOf(RujukanLab::class);
    expect(RujukanLab::count())->toBe(1);
    expect($rujukan->rujukanLabDetail()->count())->toBe(2);

    $permintaan = Permintaan::where('rujukan_lab_id', $rujukan->id)->firstOrFail();
    expect($permintaan->sumber)->toBe('rujukan')
        ->and($permintaan->permintaanDetail()->count())->toBe(2);

    // tarif snapshot
    $detail = $permintaan->permintaanDetail()->first();
    expect((float) $detail->tarif)->toBeIn([25000.0, 15000.0]);

    // antrean LAB
    $antreanLab = Antrean::where('kunjungan_id', $pemeriksaan->kunjungan_id)->whereHas('poli', fn ($q) => $q->where('kode', 'LAB'))->first();
    expect($antreanLab)->not->toBeNull()
        ->and($antreanLab->nomor_antrean)->toMatch('/^LAB-\d{3}$/');

    $pemeriksaan->kunjungan->refresh();
    expect($pemeriksaan->kunjungan->status)->toBe('lab');

    Event::assertDispatched(LabDiminta::class);
});

test('satu detail per kode pemeriksaan', function () {
    [$pemeriksaan, $user] = setupPemeriksaanForRujukan();
    $lab1 = PemeriksaanLab::create(['kode' => 'LAB01', 'nama' => 'Darah Lengkap', 'tarif' => 25000, 'aktif' => true]);

    app(RujukanLabService::class)->buat($pemeriksaan, [$lab1->id], null, $user);

    $permintaan = Permintaan::first();
    expect($permintaan->permintaanDetail()->count())->toBe(1);
});

test('atomik: gagal sebagian tidak menyimpan apa pun', function () {
    [$pemeriksaan, $user] = setupPemeriksaanForRujukan();
    $lab1 = PemeriksaanLab::create(['kode' => 'LAB01', 'nama' => 'Darah Lengkap', 'tarif' => 25000, 'aktif' => true]);

    try {
        app(RujukanLabService::class)->buat($pemeriksaan, [$lab1->id, 999999], null, $user);
    } catch (Throwable $e) {
        // expected
    }

    expect(RujukanLab::count())->toBe(0)
        ->and(Permintaan::count())->toBe(0);
});

test('event tidak terpancar jika transaksi gagal', function () {
    [$pemeriksaan, $user] = setupPemeriksaanForRujukan();

    Event::fake([LabDiminta::class]);

    try {
        app(RujukanLabService::class)->buat($pemeriksaan, [999999], null, $user);
    } catch (Throwable $e) {
        // expected
    }

    Event::assertNotDispatched(LabDiminta::class);
});
