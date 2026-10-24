<?php

use App\Models\Master\Pasien;
use App\Models\Master\Poli;
use App\Models\Sys\HakAkses;
use App\Models\User;
use App\Services\Registrasi\AntreanService;
use App\Services\Registrasi\KunjunganService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    buatMenu('dasbor', 'dashboard', 'Dasbor');
    buatMenu('pendaftaran.antrean', 'antrean.index', 'Antrean per Poli');
});

test('pengguna dengan hak dapat membuka halaman antrean', function () {
    $this->actingAs(penggunaDenganIzin(['dasbor', 'pendaftaran.antrean']))
        ->get(route('antrean.index'))
        ->assertOk();
});

test('layar antrean hanya menampilkan nomor dan status, tanpa data pribadi', function () {
    $pasien = Pasien::factory()->create(['nama_lengkap' => 'Siti Rahasia', 'nik' => '3201234567890009']);
    $poli = Poli::factory()->create(['kode' => 'INT', 'nama' => 'Poli Penyakit Dalam', 'aktif' => true]);
    $user = User::factory()->create();
    $kunjungan = app(KunjunganService::class)->buatKunjunganPoli($pasien, $user);
    app(AntreanService::class)->ambilNomor($poli, $kunjungan);

    $aktor = penggunaDenganIzin(['dasbor', 'pendaftaran.antrean']);

    $this->actingAs($aktor)->get(route('antrean.layar'))->assertOk();

    $respons = $this->actingAs($aktor)->getJson(route('antrean.json'));
    $json = $respons->getContent();
    expect($json)->not->toContain('Siti Rahasia')
        ->and($json)->not->toContain('3201234567890009');
});

test('pengguna tanpa hak ditolak membuka halaman antrean', function () {
    $this->actingAs(penggunaDenganIzin(['dasbor']))
        ->get(route('antrean.index'))
        ->assertForbidden();
});

test('endpoint json hanya memuat field yang disetujui', function () {
    $pasien = Pasien::factory()->create(['nama_lengkap' => 'Siti Aminah']);
    $poli = Poli::factory()->create(['kode' => 'INT', 'nama' => 'Poli Penyakit Dalam', 'aktif' => true]);
    $user = User::factory()->create();
    $kunjungan = app(KunjunganService::class)->buatKunjunganPoli($pasien, $user);
    app(AntreanService::class)->ambilNomor($poli, $kunjungan);

    $respons = $this->actingAs(penggunaDenganIzin(['dasbor', 'pendaftaran.antrean']))
        ->getJson(route('antrean.json'))
        ->assertOk();

    $item = $respons->json('items.0');
    expect(array_keys($item))->toBe(['id', 'nomor', 'status', 'inisial']);
    expect($respons->json('menunggu'))->toBeInt();
});

test('transisi status antrean: panggil, layani, selesai', function () {
    $pasien = Pasien::factory()->create();
    $poli = Poli::factory()->create(['kode' => 'INT', 'nama' => 'Poli Penyakit Dalam', 'aktif' => true]);
    $user = User::factory()->create();
    $kunjungan = app(KunjunganService::class)->buatKunjunganPoli($pasien, $user);
    $antrean = app(AntreanService::class)->ambilNomor($poli, $kunjungan);

    $aktor = penggunaDenganIzin(['dasbor', 'pendaftaran.antrean']);

    $this->actingAs($aktor)->post(route('antrean.panggil', $antrean))->assertRedirect();
    $antrean->refresh();
    expect($antrean->status)->toBe('dipanggil');

    $this->actingAs($aktor)->post(route('antrean.layani', $antrean))->assertRedirect();
    $antrean->refresh();
    expect($antrean->status)->toBe('dilayani');

    $this->actingAs($aktor)->post(route('antrean.selesai', $antrean))->assertRedirect();
    $antrean->refresh();
    expect($antrean->status)->toBe('selesai');
});

test('pengguna tanpa hak ubah ditolak melakukan aksi status', function () {
    $pasien = Pasien::factory()->create();
    $poli = Poli::factory()->create(['kode' => 'INT', 'nama' => 'Poli Penyakit Dalam', 'aktif' => true]);
    $user = User::factory()->create();
    $kunjungan = app(KunjunganService::class)->buatKunjunganPoli($pasien, $user);
    $antrean = app(AntreanService::class)->ambilNomor($poli, $kunjungan);

    $lihatSaja = penggunaHanyaLihat('pendaftaran.antrean');
    // ensure dasbor access
    $menuDasbor = buatMenu('dasbor', 'dashboard', 'Dasbor');
    HakAkses::firstOrCreate(
        ['peran_id' => $lihatSaja->peran_id, 'menu_id' => $menuDasbor->id],
        ['bisa_lihat' => true, 'bisa_tambah' => false, 'bisa_ubah' => false, 'bisa_hapus' => false]
    );

    $this->actingAs($lihatSaja)
        ->post(route('antrean.panggil', $antrean))
        ->assertForbidden();
});
