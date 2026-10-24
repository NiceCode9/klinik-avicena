<?php

use App\Models\Master\Pasien;
use App\Models\Master\Poli;
use App\Models\Registrasi\Antrean;
use App\Models\Registrasi\Kunjungan;
use App\Services\Registrasi\KunjunganService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    buatMenu('dasbor', 'dashboard', 'Dasbor');
    buatMenu('pendaftaran.kunjungan', 'kunjungan.index', 'Kunjungan & Antrean');
    buatMenu('pendaftaran.ttv', 'ttv.index', 'Pemeriksaan TTV');
    buatMenu('pendaftaran.pasien', 'pasien.index', 'Data Pasien');
});

function dataTtvLengkap2(array $ganti = []): array
{
    return $ganti + [
        'poli_id' => null,
        'tekanan_darah_sistolik' => 120,
        'tekanan_darah_diastolik' => 80,
        'nadi' => 80,
        'suhu' => 36.5,
        'berat_badan' => 60.5,
    ];
}

test('alur lengkap: cari pasien, buat kunjungan, input TTV, terbit nomor antrean', function () {
    $pasien = Pasien::factory()->create(['nik' => '3201234567890001', 'nama_lengkap' => 'Siti Aminah']);
    $poli = Poli::factory()->create(['kode' => 'INT', 'nama' => 'Poli Penyakit Dalam', 'aktif' => true]);

    $user = penggunaDenganIzin(['dasbor', 'pendaftaran.kunjungan', 'pendaftaran.ttv']);

    $this->actingAs($user)
        ->get(route('kunjungan.create', ['nik' => '3201234567890001']))
        ->assertOk()
        ->assertSee('Siti Aminah');

    $respons = $this->actingAs($user)
        ->post(route('kunjungan.store'), ['pasien_id' => $pasien->id])
        ->assertRedirect();

    $kunjungan = Kunjungan::where('pasien_id', $pasien->id)->firstOrFail();

    $this->actingAs($user)
        ->get(route('ttv.create', $kunjungan))
        ->assertOk();

    $dataTtv = dataTtvLengkap2(['poli_id' => $poli->id]);

    $this->actingAs($user)
        ->post(route('ttv.store', $kunjungan), $dataTtv)
        ->assertRedirect(route('kunjungan.cetak', $kunjungan));

    $antrean = Antrean::where('kunjungan_id', $kunjungan->id)->firstOrFail();
    expect($antrean->nomor_antrean)->toMatch('/^INT-\d{3}$/');

    $this->actingAs($user)
        ->get(route('kunjungan.cetak', $kunjungan))
        ->assertOk()
        ->assertSee($antrean->nomor_antrean);
});

test('TTV tidak lengkap ditolak', function () {
    $pasien = Pasien::factory()->create(['nik' => '3201234567890002']);
    $poli = Poli::factory()->create(['kode' => 'INT', 'nama' => 'Poli Penyakit Dalam', 'aktif' => true]);
    $user = penggunaDenganIzin(['dasbor', 'pendaftaran.kunjungan', 'pendaftaran.ttv']);

    $kunjungan = app(KunjunganService::class)->buatKunjunganPoli($pasien, $user);

    $this->actingAs($user)
        ->post(route('ttv.store', $kunjungan), ['poli_id' => $poli->id])
        ->assertSessionHasErrors();
});

test('resepsionis tanpa hak TTV mendapat 403 pada halaman input TTV', function () {
    $pasien = Pasien::factory()->create(['nik' => '3201234567890003']);
    $user = penggunaDenganIzin(['dasbor', 'pendaftaran.kunjungan']);

    $kunjungan = app(KunjunganService::class)->buatKunjunganPoli($pasien, $user);

    $this->actingAs($user)
        ->get(route('ttv.create', $kunjungan))
        ->assertForbidden();
});

test('pasien tidak ditemukan menampilkan tautan tambah pasien', function () {
    $user = penggunaDenganIzin(['dasbor', 'pendaftaran.kunjungan']);

    $this->actingAs($user)
        ->get(route('kunjungan.create', ['nik' => '9999999999999999']))
        ->assertOk()
        ->assertSee('Tambah pasien baru');
});
