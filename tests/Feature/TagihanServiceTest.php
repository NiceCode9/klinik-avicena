<?php

use App\Models\Apotek\PenjualanUmum;
use App\Models\Billing\Pembayaran;
use App\Models\Billing\Tagihan;
use App\Models\Erm\Pemeriksaan;
use App\Models\Lab\Permintaan;
use App\Models\Lab\PermintaanDetail;
use App\Models\Master\Dokter;
use App\Models\Master\MetodeBayar;
use App\Models\Master\Pasien;
use App\Models\Master\PemeriksaanLab;
use App\Models\Master\Poli;
use App\Models\Registrasi\Kunjungan;
use App\Models\Sys\Pengaturan;
use App\Models\User;
use App\Services\Billing\TagihanService;
use App\Services\Erm\Exceptions\DataTerkunciException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @return array{0: Kunjungan, 1: Pemeriksaan, 2: User}
 */
function buatKunjunganTagihan(float $tarif = 75000): array
{
    $poli = Poli::factory()->create(['kode' => 'INT', 'nama' => 'Poli Penyakit Dalam', 'aktif' => true]);
    $pasien = Pasien::factory()->create();
    $dokter = Dokter::factory()->create(['poli_id' => $poli->id, 'tarif_konsultasi' => $tarif]);
    $user = User::factory()->create();
    $kunjungan = Kunjungan::factory()->create([
        'pasien_id' => $pasien->id,
        'jenis_kunjungan' => 'poli',
        'status' => 'diperiksa',
        'dibuat_oleh' => $user->id,
    ]);
    $pemeriksaan = Pemeriksaan::factory()->create([
        'kunjungan_id' => $kunjungan->id,
        'poli_id' => $poli->id,
        'dokter_id' => $dokter->id,
        'tarif_konsultasi' => $tarif,
    ]);

    return [$kunjungan, $pemeriksaan, $user];
}

function buatPermintaanLabTagihan(Kunjungan $kunjungan, array $tarif = [30000, 15000], bool $dikunci = true): Permintaan
{
    $permintaan = Permintaan::factory()->for($kunjungan)->create(['dikunci' => $dikunci]);

    foreach ($tarif as $indeks => $nilai) {
        $lab = PemeriksaanLab::create([
            'kode' => 'L'.fake()->unique()->numerify('#####').$indeks,
            'nama' => 'Pemeriksaan '.$indeks,
            'tarif' => $nilai,
            'aktif' => true,
        ]);
        PermintaanDetail::create([
            'permintaan_id' => $permintaan->id,
            'pemeriksaan_lab_id' => $lab->id,
            'tarif' => $nilai,
        ]);
    }

    return $permintaan;
}

test('susunFarmasi tanpa resep tetap membuat konsultasi dan biaya tambahan tepat satu baris', function () {
    Pengaturan::create(['kunci' => 'uang_kopi', 'nilai' => '5000']);
    Pengaturan::create(['kunci' => 'simpanan_default', 'nilai' => '10000']);
    [$kunjungan] = buatKunjunganTagihan(75000);

    $tagihan = app(TagihanService::class)->susunFarmasi($kunjungan);

    expect($tagihan->jenis)->toBe('farmasi')
        ->and($tagihan->tagihanDetail()->where('komponen', 'konsultasi')->count())->toBe(1)
        ->and($tagihan->tagihanDetail()->where('komponen', 'uang_kopi')->count())->toBe(1)
        ->and($tagihan->tagihanDetail()->where('komponen', 'simpanan')->count())->toBe(1)
        ->and((string) $tagihan->total)->toBe('90000.00');
});

test('susunFarmasi idempoten saat dipanggil berulang', function () {
    [$kunjungan] = buatKunjunganTagihan(50000);

    $pertama = app(TagihanService::class)->susunFarmasi($kunjungan);
    $idAwal = $pertama->tagihanDetail()->pluck('id')->sort()->values()->all();
    $kedua = app(TagihanService::class)->susunFarmasi($kunjungan);

    expect($kedua->id)->toBe($pertama->id)
        ->and($kedua->tagihanDetail()->pluck('id')->sort()->values()->all())->toBe($idAwal)
        ->and(Tagihan::where('kunjungan_id', $kunjungan->id)->where('jenis', 'farmasi')->count())->toBe(1);
});

test('susunFarmasi memakai tarif konsultasi snapshot pemeriksaan', function () {
    [$kunjungan, $pemeriksaan] = buatKunjunganTagihan(50000);
    $pemeriksaan->dokter->update(['tarif_konsultasi' => 90000]);

    $tagihan = app(TagihanService::class)->susunFarmasi($kunjungan);
    $konsultasi = $tagihan->tagihanDetail()->where('komponen', 'konsultasi')->first();

    expect((string) $konsultasi->harga_satuan)->toBe('50000.00');
});

test('tagihan farmasi lunas tidak bisa disusun ulang', function () {
    [$kunjungan] = buatKunjunganTagihan();
    $tagihan = app(TagihanService::class)->susunFarmasi($kunjungan);
    $tagihan->update(['status' => 'lunas']);

    expect(fn () => app(TagihanService::class)->susunFarmasi($kunjungan))
        ->toThrow(DataTerkunciException::class);
});

test('susunFarmasi penjualan umum membuat tagihan farmasi tanpa biaya poli', function () {
    $penjualan = PenjualanUmum::create([
        'no_penjualan' => 'PJ-0001',
        'tanggal' => today(),
        'status' => 'menunggu_bayar',
    ]);

    $tagihan = app(TagihanService::class)->susunFarmasi($penjualan);

    expect($tagihan->penjualan_umum_id)->toBe($penjualan->id)
        ->and($tagihan->kunjungan_id)->toBeNull()
        ->and($tagihan->jenis)->toBe('farmasi')
        ->and($tagihan->tagihanDetail()->count())->toBe(0);
});

test('susunLab membuat rincian dari snapshot tarif permintaan', function () {
    [$kunjungan] = buatKunjunganTagihan();
    buatPermintaanLabTagihan($kunjungan, [30000, 15000]);

    $tagihan = app(TagihanService::class)->susunLab($kunjungan);

    expect($tagihan)->not->toBeNull()
        ->and($tagihan->jenis)->toBe('lab')
        ->and($tagihan->tagihanDetail()->count())->toBe(2)
        ->and((string) $tagihan->total)->toBe('45000.00');
});

test('susunLab idempoten saat dipanggil berulang', function () {
    [$kunjungan] = buatKunjunganTagihan();
    buatPermintaanLabTagihan($kunjungan, [20000]);

    $pertama = app(TagihanService::class)->susunLab($kunjungan);
    $idAwal = $pertama->tagihanDetail()->pluck('id')->all();
    $kedua = app(TagihanService::class)->susunLab($kunjungan);

    expect($kedua->id)->toBe($pertama->id)
        ->and($kedua->tagihanDetail()->pluck('id')->all())->toBe($idAwal)
        ->and(Tagihan::where('kunjungan_id', $kunjungan->id)->where('jenis', 'lab')->count())->toBe(1);
});

test('susunLab menunggu semua permintaan lab terkunci', function () {
    [$kunjungan] = buatKunjunganTagihan();
    buatPermintaanLabTagihan($kunjungan, [10000], false);

    expect(app(TagihanService::class)->susunLab($kunjungan))->toBeNull()
        ->and(Tagihan::where('kunjungan_id', $kunjungan->id)->where('jenis', 'lab')->exists())->toBeFalse();
});

test('tagihan lab lunas tidak bisa disusun ulang', function () {
    [$kunjungan] = buatKunjunganTagihan();
    buatPermintaanLabTagihan($kunjungan, [20000]);
    $tagihan = app(TagihanService::class)->susunLab($kunjungan);
    $tagihan->update(['status' => 'lunas']);

    expect(fn () => app(TagihanService::class)->susunLab($kunjungan))
        ->toThrow(DataTerkunciException::class);
});

test('satu kunjungan memiliki satu tagihan farmasi dan satu tagihan lab', function () {
    [$kunjungan] = buatKunjunganTagihan(40000);
    buatPermintaanLabTagihan($kunjungan, [20000]);

    $farmasi = app(TagihanService::class)->susunFarmasi($kunjungan);
    $lab = app(TagihanService::class)->susunLab($kunjungan);

    expect(Tagihan::where('kunjungan_id', $kunjungan->id)->count())->toBe(2)
        ->and($farmasi->id)->not->toBe($lab->id)
        ->and($farmasi->jenis)->toBe('farmasi')
        ->and($lab->jenis)->toBe('lab')
        ->and((string) $farmasi->total)->toBe('40000.00')
        ->and((string) $lab->total)->toBe('20000.00');
});

test('total selalu subtotal dikurangi diskon dengan bcmath', function () {
    Pengaturan::create(['kunci' => 'uang_kopi', 'nilai' => '1500.10']);
    Pengaturan::create(['kunci' => 'simpanan_default', 'nilai' => '2500.20']);
    [$kunjungan] = buatKunjunganTagihan(1000.05);

    $tagihan = app(TagihanService::class)->susunFarmasi($kunjungan);
    $tagihan->update(['diskon' => '500.05']);
    app(TagihanService::class)->tambahDetail($tagihan, [
        'komponen' => 'lainnya',
        'deskripsi' => 'Biaya tambahan',
        'harga_satuan' => '0.10',
    ]);

    $tagihan->refresh();

    expect((string) $tagihan->subtotal)->toBe('5000.45')
        ->and((string) $tagihan->total)->toBe('4500.40');
});

test('tagihan dianggap lunas saat jumlah pembayaran mencapai total', function () {
    [$kunjungan] = buatKunjunganTagihan(75000);
    $tagihan = app(TagihanService::class)->susunFarmasi($kunjungan);
    $metode = MetodeBayar::create(['nama' => 'Tunai', 'aktif' => true]);
    $kasir = User::factory()->create();

    Pembayaran::create([
        'tagihan_id' => $tagihan->id,
        'metode_bayar_id' => $metode->id,
        'jumlah_bayar' => '50000.00',
        'kembalian' => '0',
        'kasir_id' => $kasir->id,
        'dibayar_pada' => now(),
    ]);
    expect(app(TagihanService::class)->lunas($tagihan->refresh()))->toBeFalse();

    Pembayaran::create([
        'tagihan_id' => $tagihan->id,
        'metode_bayar_id' => $metode->id,
        'jumlah_bayar' => '25000.00',
        'kembalian' => '0',
        'kasir_id' => $kasir->id,
        'dibayar_pada' => now(),
    ]);

    expect(app(TagihanService::class)->lunas($tagihan->refresh()))->toBeTrue();
});
