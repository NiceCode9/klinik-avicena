<?php

use App\Events\LabDikunci;
use App\Events\SpesimenDiambil;
use App\Models\Billing\Tagihan;
use App\Models\Lab\Hasil;
use App\Models\Lab\Permintaan;
use App\Models\Master\Dokter;
use App\Models\Master\ParameterLab;
use App\Models\Master\Pasien;
use App\Models\Master\PemeriksaanLab;
use App\Models\Master\Poli;
use App\Models\User;
use App\Services\Erm\Exceptions\DataTerkunciException;
use App\Services\Erm\PemeriksaanService;
use App\Services\Erm\RujukanLabService;
use App\Services\Lab\LabService;
use App\Services\Lab\SpesimenService;
use App\Services\Registrasi\AntreanService;
use App\Services\Registrasi\KunjunganService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

function setupPermintaanLabSmoke(): array
{
    $poli = Poli::factory()->create(['kode' => 'INT', 'nama' => 'Poli PD', 'aktif' => true]);
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

    $lab = PemeriksaanLab::create(['kode' => 'HMT', 'nama' => 'Hematologi', 'tarif' => 30000, 'aktif' => true]);
    ParameterLab::create(['pemeriksaan_lab_id' => $lab->id, 'nama_parameter' => 'Hemoglobin', 'satuan' => 'g/dL', 'nilai_rujukan_min' => 12, 'nilai_rujukan_max' => 16, 'urutan' => 0]);

    app(RujukanLabService::class)->buat($pemeriksaan, [$lab->id], null, $user);

    $permintaan = Permintaan::first();

    return [$permintaan, $user, $lab];
}

test('tentukanPenanda kasus', function () {
    expect(LabService::tentukanPenanda(14, 12, 16))->toBe('normal')
        ->and(LabService::tentukanPenanda(10, 12, 16))->toBe('rendah')
        ->and(LabService::tentukanPenanda(20, 12, 16))->toBe('tinggi')
        ->and(LabService::tentukanPenanda(12, 12, 16))->toBe('normal')
        ->and(LabService::tentukanPenanda(16, 12, 16))->toBe('normal')
        ->and(LabService::tentukanPenanda('abc', 12, 16))->toBeNull();
});

test('mulaiProses set status diproses', function () {
    [$permintaan, $user] = setupPermintaanLabSmoke();
    $p = app(LabService::class)->mulaiProses($permintaan, $user);
    expect($p->status)->toBe('diproses');
});

test('simpanHasil snapshot dan penanda; ditolak jika spesimen belum diterima', function () {
    [$permintaan, $user, $lab] = setupPermintaanLabSmoke();
    $detail = $permintaan->permintaanDetail()->first();
    $param = $lab->parameterLab()->first();

    // link specimen not received
    $spesimen = app(SpesimenService::class)->catat($permintaan, [
        'kode_spesimen' => 'X',
        'nama_spesimen' => 'X',
        'status' => 'diambil',
    ], $user);
    app(SpesimenService::class)->tautkan($detail, $spesimen);

    expect(fn () => app(LabService::class)->simpanHasil($detail, [
        'parameter_lab_id' => $param->id,
        'nilai' => '14',
    ], $user))->toThrow(InvalidArgumentException::class);

    // set received
    app(SpesimenService::class)->ubahStatus($spesimen, 'diterima', $user);

    $hasil = app(LabService::class)->simpanHasil($detail, [
        'parameter_lab_id' => $param->id,
        'nilai' => '14',
    ], $user);

    expect($hasil)->toBeInstanceOf(Hasil::class)
        ->and($hasil->satuan)->toBe('g/dL')
        ->and($hasil->penanda)->toBe('normal');
});

test('spesimen ditolak wajib catatan; event spesimen terpancar', function () {
    [$permintaan, $user] = setupPermintaanLabSmoke();

    Event::fake([SpesimenDiambil::class]);

    $spesimen = app(SpesimenService::class)->catat($permintaan, [
        'kode_spesimen' => 'X',
        'nama_spesimen' => 'X',
    ], $user);

    Event::assertDispatched(SpesimenDiambil::class);

    expect(fn () => app(SpesimenService::class)->ubahStatus($spesimen, 'ditolak', null, $user))
        ->toThrow(InvalidArgumentException::class);
});

test('kunci tanpa hasil ditolak; setelah kunci terkunci; event lab dikunci', function () {
    [$permintaan, $user, $lab] = setupPermintaanLabSmoke();
    $detail = $permintaan->permintaanDetail()->first();
    $param = $lab->parameterLab()->first();

    $permintaan->update(['dokter_id' => Dokter::first()->id]);

    // create specimen received
    $spesimen = app(SpesimenService::class)->catat($permintaan, ['kode_spesimen' => 'X', 'nama_spesimen' => 'X', 'status' => 'diterima'], $user);
    app(SpesimenService::class)->tautkan($detail, $spesimen);

    // no hasil yet
    expect(fn () => app(LabService::class)->kunci($permintaan, $user))
        ->toThrow(InvalidArgumentException::class);

    // save result
    app(LabService::class)->simpanHasil($detail, ['parameter_lab_id' => $param->id, 'nilai' => '14'], $user);

    Event::fake([LabDikunci::class]);

    $locked = app(LabService::class)->kunci($permintaan, $user);

    $tagihan = Tagihan::where('kunjungan_id', $permintaan->kunjungan_id)->where('jenis', 'lab')->first();

    expect($locked->dikunci)->toBeTrue()
        ->and($permintaan->fresh()->status)->toBe('selesai')
        ->and($tagihan)->not->toBeNull()
        ->and($tagihan->tagihanDetail()->count())->toBe(1)
        ->and((string) $tagihan->tagihanDetail()->first()->harga_satuan)->toBe('30000.00')
        ->and($permintaan->kunjungan->fresh()->status)->toBe('lab');

    // attempt change rejected
    expect(fn () => app(LabService::class)->simpanHasil($detail, ['parameter_lab_id' => $param->id, 'nilai' => '15'], $user))
        ->toThrow(DataTerkunciException::class);

    Event::assertDispatched(LabDikunci::class);
});
