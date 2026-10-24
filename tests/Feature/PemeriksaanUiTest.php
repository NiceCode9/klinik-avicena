<?php

use App\Models\Master\Dokter;
use App\Models\Master\Icd10;
use App\Models\Master\Pasien;
use App\Models\Master\Poli;
use App\Models\Sys\HakAkses;
use App\Models\Sys\Peran;
use App\Models\User;
use App\Services\Erm\PemeriksaanService;
use App\Services\Registrasi\AntreanService;
use App\Services\Registrasi\KunjunganService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    buatMenu('dasbor', 'dashboard', 'Dasbor');
    buatMenu('erm.pemeriksaan', 'pemeriksaan.index', 'Pemeriksaan (SOAP)');
});

function setupKunjunganUntukDokter(): array
{
    $poli = Poli::factory()->create(['kode' => 'INT', 'nama' => 'Poli Penyakit Dalam', 'aktif' => true]);
    $pasien = Pasien::factory()->create();
    $dokter = Dokter::factory()->create(['poli_id' => $poli->id]);
    $peran = Peran::create(['nama' => 'dokter_test']);
    $user = User::factory()->create(['dokter_id' => $dokter->id, 'peran_id' => $peran->id]);
    $user->update(['peran_id' => $peran->id]);
    $menu = buatMenu('erm.pemeriksaan');
    HakAkses::create([
        'peran_id' => $peran->id,
        'menu_id' => $menu->id,
        'bisa_lihat' => true,
        'bisa_tambah' => true,
        'bisa_ubah' => true,
        'bisa_hapus' => false,
    ]);
    $menuDasbor = buatMenu('dasbor', 'dashboard', 'Dasbor');
    HakAkses::firstOrCreate(
        ['peran_id' => $peran->id, 'menu_id' => $menuDasbor->id],
        ['bisa_lihat' => true, 'bisa_tambah' => true, 'bisa_ubah' => true, 'bisa_hapus' => true]
    );

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

    return [$kunjungan, $antrean, $dokter, $user, $pasien];
}

test('non-dokter (tanpa dokter_id) mendapat 403 pada mulai pemeriksaan', function () {
    $poli = Poli::factory()->create(['kode' => 'INT', 'nama' => 'Poli Penyakit Dalam', 'aktif' => true]);
    $pasien = Pasien::factory()->create();
    $peran = Peran::create(['nama' => 'resepsionis_test']);
    $user = User::factory()->create(['peran_id' => $peran->id]);
    $user->update(['peran_id' => $peran->id]);
    $menu = buatMenu('erm.pemeriksaan');
    HakAkses::create([
        'peran_id' => $peran->id,
        'menu_id' => $menu->id,
        'bisa_lihat' => true,
        'bisa_tambah' => true,
        'bisa_ubah' => true,
        'bisa_hapus' => false,
    ]);
    $kunjungan = app(KunjunganService::class)->buatKunjunganPoli($pasien, $user);

    $this->actingAs($user)
        ->post(route('pemeriksaan.mulai', $kunjungan))
        ->assertForbidden();
});

test('dokter lain tidak bisa mengubah pemeriksaan', function () {
    [$kunjungan, $antrean, $dokter, $user, $pasien] = setupKunjunganUntukDokter();
    $pemeriksaan = app(PemeriksaanService::class)->mulai($antrean, $dokter, $user);

    // dokter lain
    $poli2 = Poli::factory()->create(['kode' => 'GIG', 'nama' => 'Poli Gigi', 'aktif' => true]);
    $dokter2 = Dokter::factory()->create(['poli_id' => $poli2->id]);
    $peran2 = Peran::create(['nama' => 'dokter_lain']);
    $user2 = User::factory()->create(['dokter_id' => $dokter2->id, 'peran_id' => $peran2->id]);
    $user2->update(['peran_id' => $peran2->id]);
    $menu = buatMenu('erm.pemeriksaan');
    HakAkses::create([
        'peran_id' => $peran2->id,
        'menu_id' => $menu->id,
        'bisa_lihat' => true,
        'bisa_tambah' => true,
        'bisa_ubah' => true,
        'bisa_hapus' => false,
    ]);
    $menuDasbor = buatMenu('dasbor', 'dashboard', 'Dasbor');
    HakAkses::firstOrCreate(
        ['peran_id' => $peran2->id, 'menu_id' => $menuDasbor->id],
        ['bisa_lihat' => true, 'bisa_tambah' => true, 'bisa_ubah' => true, 'bisa_hapus' => true]
    );

    $this->actingAs($user2)
        ->get(route('pemeriksaan.edit', $pemeriksaan))
        ->assertOk(); // can view

    $this->actingAs($user2)
        ->put(route('pemeriksaan.update', $pemeriksaan), ['subjektif' => 'hack', 'aksi' => 'draf'])
        ->assertForbidden();
});

test('autocomplete ICD-10 membatasi hasil maksimal 20', function () {
    for ($i = 0; $i < 25; $i++) {
        Icd10::create(['kode' => 'Z'.str_pad((string) $i, 3, '0', STR_PAD_LEFT), 'nama_diagnosis' => 'Diagnosis '.$i]);
    }

    $user = penggunaDenganIzin(['dasbor', 'erm.pemeriksaan']);
    $respons = $this->actingAs($user)
        ->getJson(route('pemeriksaan.icd10', ['q' => 'Diagnosis']))
        ->assertOk();

    expect(count($respons->json()))->toBeLessThanOrEqual(20);
});
