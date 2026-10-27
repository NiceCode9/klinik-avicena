<?php

use App\Models\Master\Pasien;
use App\Models\Master\Poli;
use App\Models\Registrasi\Kunjungan;
use App\Models\Sys\LogAktivitas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    buatMenu('dasbor', 'dashboard', 'Dasbor');
    buatMenu('erm.pemeriksaan', 'pemeriksaan.index', 'Pemeriksaan (SOAP)');
});

test('non-dokter (tanpa hak) ditolak membuka riwayat', function () {
    $pasien = Pasien::factory()->create();
    $user = penggunaDenganIzin(['dasbor']);

    $this->actingAs($user)
        ->get(route('riwayat.index', $pasien))
        ->assertForbidden();
});

test('halaman riwayat dapat dibuka dan NIK tersamarkan', function () {
    $poli = Poli::factory()->create(['kode' => 'INT', 'nama' => 'Poli Penyakit Dalam', 'aktif' => true]);
    $pasien = Pasien::factory()->create(['nik' => '3201234567890001', 'nama_lengkap' => 'Siti Aminah']);
    Kunjungan::factory()->create(['pasien_id' => $pasien->id, 'status' => 'selesai', 'tanggal_kunjungan' => today()]);

    $user = penggunaDenganIzin(['dasbor', 'erm.pemeriksaan']);

    $this->actingAs($user)
        ->get(route('riwayat.index', $pasien))
        ->assertOk()
        ->assertSee('Siti Aminah')
        ->assertDontSee('3201234567890001');
});

test('detail riwayat tercatat di log aktivitas', function () {
    $pasien = Pasien::factory()->create();
    $kunjungan = Kunjungan::factory()->create(['pasien_id' => $pasien->id, 'status' => 'selesai', 'tanggal_kunjungan' => today()]);

    $user = penggunaDenganIzin(['dasbor', 'erm.pemeriksaan']);

    $this->actingAs($user)
        ->get(route('riwayat.show', [$pasien, $kunjungan]))
        ->assertOk();

    expect(LogAktivitas::where('aksi', 'lihat_riwayat_pasien')
        ->where('data_id', $kunjungan->id)
        ->exists())->toBeTrue();
});

test('index riwayat tidak menimbulkan N+1 (jumlah query terbatas)', function () {
    $poli = Poli::factory()->create(['kode' => 'INT', 'nama' => 'Poli Penyakit Dalam', 'aktif' => true]);
    $pasien = Pasien::factory()->create();
    for ($i = 0; $i < 5; $i++) {
        Kunjungan::factory()->create(['pasien_id' => $pasien->id, 'status' => 'selesai', 'tanggal_kunjungan' => today()]);
    }

    $user = penggunaDenganIzin(['dasbor', 'erm.pemeriksaan']);

    DB::enableQueryLog();
    $this->actingAs($user)->get(route('riwayat.index', $pasien))->assertOk();
    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    // With eager loading, query count should be small and not scale with item count.
    expect(count($queries))->toBeLessThanOrEqual(8);
});
