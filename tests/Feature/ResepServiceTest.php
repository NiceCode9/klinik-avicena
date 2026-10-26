<?php

use App\Events\ResepDiterbitkan;
use App\Models\Master\Dokter;
use App\Models\Master\KategoriObat;
use App\Models\Master\Obat;
use App\Models\Master\Pasien;
use App\Models\Master\Poli;
use App\Models\Master\Racikan;
use App\Models\Master\RacikanBahan;
use App\Models\Master\Satuan;
use App\Models\User;
use App\Services\Erm\PemeriksaanService;
use App\Services\Erm\ResepService;
use App\Services\Registrasi\AntreanService;
use App\Services\Registrasi\KunjunganService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

function setupPemeriksaanForResep(): array
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

function buatObatUji(): Obat
{
    $kategori = KategoriObat::firstOrCreate(['nama' => 'Umum']);
    $satuan = Satuan::firstOrCreate(['nama' => 'tablet']);

    return Obat::create([
        'kode' => 'OBT-001',
        'nama' => 'Paracetamol',
        'kategori_obat_id' => $kategori->id,
        'satuan_id' => $satuan->id,
        'golongan' => 'bebas',
        'harga_jual' => 5000,
        'aktif' => true,
    ]);
}

test('buatResep membuat resep status diterbitkan dan memancarkan event', function () {
    [$pemeriksaan, $user] = setupPemeriksaanForResep();

    Event::fake([ResepDiterbitkan::class]);

    $resep = app(ResepService::class)->buatResep($pemeriksaan, $user);

    expect($resep->status)->toBe('diterbitkan')
        ->and($resep->no_resep)->toMatch('/^RP-\d{8}-\d{4}$/');

    Event::assertDispatched(ResepDiterbitkan::class);
});

test('tambahItem reguler snapshot harga dan auto aturan_pakai', function () {
    [$pemeriksaan, $user] = setupPemeriksaanForResep();
    $obat = buatObatUji();
    $resep = app(ResepService::class)->buatResep($pemeriksaan, $user);

    $item = app(ResepService::class)->tambahItem($resep, [
        'jenis' => 'reguler',
        'obat_id' => $obat->id,
        'jumlah' => 10,
        'dosis' => 1,
        'satuan_dosis' => 'tablet',
        'frekuensi' => 3,
        'periode' => 1,
        'satuan_periode' => 'hari',
        'kode_rute' => 'PO',
        'nama_rute' => 'Per oral',
        'lama_hari' => 5,
    ], $user);

    expect((float) $item->harga_satuan)->toBe(5000.0)
        ->and($item->aturan_pakai)->toContain('1 tablet')
        ->and($item->obat_id)->toBe($obat->id);
});

test('resep tidak bisa diubah setelah divalidasi', function () {
    [$pemeriksaan, $user] = setupPemeriksaanForResep();
    $obat = buatObatUji();
    $resep = app(ResepService::class)->buatResep($pemeriksaan, $user);
    $resep->update(['status' => 'divalidasi']);

    expect(fn () => app(ResepService::class)->tambahItem($resep, [
        'jenis' => 'reguler',
        'obat_id' => $obat->id,
        'jumlah' => 1,
    ], $user))->toThrow(RuntimeException::class);
});

test('racikan salin dari template', function () {
    [$pemeriksaan, $user] = setupPemeriksaanForResep();
    $obat = buatObatUji();
    $satuan = Satuan::firstOrCreate(['nama' => 'tablet']);
    $racikan = Racikan::create(['nama_menu' => 'Puyer', 'aktif' => true]);
    RacikanBahan::create([
        'racikan_id' => $racikan->id,
        'obat_id' => $obat->id,
        'jumlah' => 2,
        'satuan_id' => $satuan->id,
    ]);

    $resep = app(ResepService::class)->buatResep($pemeriksaan, $user);

    $item = app(ResepService::class)->salinRacikan($resep, $racikan, [
        'jumlah' => 3,
        'dosis' => 1,
        'satuan_dosis' => 'bungkus',
    ], $user);

    expect($item->jenis)->toBe('racikan')
        ->and($item->resepRacikanBahan()->count())->toBe(1);
});

test('jumlah <= 0 ditolak', function () {
    [$pemeriksaan, $user] = setupPemeriksaanForResep();
    $obat = buatObatUji();
    $resep = app(ResepService::class)->buatResep($pemeriksaan, $user);

    expect(fn () => app(ResepService::class)->tambahItem($resep, [
        'jenis' => 'reguler',
        'obat_id' => $obat->id,
        'jumlah' => 0,
    ], $user))->toThrow(InvalidArgumentException::class);
});
