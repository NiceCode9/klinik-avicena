<?php

use App\Models\Billing\Tagihan;
use App\Models\Lab\Permintaan;
use App\Models\Registrasi\Kunjungan;
use App\Models\Registrasi\KunjunganRiwayatStatus;
use App\Models\User;
use App\Services\Registrasi\StatusKunjunganService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function buatKunjunganSinkron(User $user, string $status = 'diperiksa', string $jenis = 'poli'): Kunjungan
{
    return Kunjungan::factory()->create([
        'jenis_kunjungan' => $jenis,
        'status' => $status,
        'dibuat_oleh' => $user->id,
    ]);
}

function buatTagihanSinkron(Kunjungan $kunjungan, string $jenis, string $status = 'belum_bayar', string $total = '10000.00'): Tagihan
{
    return Tagihan::create([
        'no_tagihan' => 'TG-'.fake()->unique()->numerify('##########'),
        'kunjungan_id' => $kunjungan->id,
        'jenis' => $jenis,
        'subtotal' => $total,
        'diskon' => '0',
        'total' => $total,
        'status' => $status,
    ]);
}

test('sinkron tidak memindahkan kunjungan diperiksa yang belum punya tagihan farmasi', function () {
    $user = User::factory()->create();
    $kunjungan = buatKunjunganSinkron($user);

    $hasil = app(StatusKunjunganService::class)->sinkron($kunjungan, $user);

    expect($hasil->status)->toBe('diperiksa')
        ->and(KunjunganRiwayatStatus::count())->toBe(0);
});

test('sinkron memindahkan ke lab saat permintaan lab belum dikunci', function () {
    $user = User::factory()->create();
    $kunjungan = buatKunjunganSinkron($user);
    Permintaan::factory()->for($kunjungan)->create(['dikunci' => false]);

    $hasil = app(StatusKunjunganService::class)->sinkron($kunjungan, $user);

    expect($hasil->status)->toBe('lab')
        ->and($hasil->riwayatStatus()->count())->toBe(1)
        ->and($hasil->riwayatStatus()->first()->status)->toBe('lab');
});

test('sinkron tetap di lab saat tagihan lab belum lunas', function () {
    $user = User::factory()->create();
    $kunjungan = buatKunjunganSinkron($user, 'lab');
    buatTagihanSinkron($kunjungan, 'lab');

    $hasil = app(StatusKunjunganService::class)->sinkron($kunjungan, $user);

    expect($hasil->status)->toBe('lab')
        ->and(KunjunganRiwayatStatus::count())->toBe(0);
});

test('sinkron memindahkan ke farmasi saat lab lunas dan tagihan farmasi belum lunas', function () {
    $user = User::factory()->create();
    $kunjungan = buatKunjunganSinkron($user, 'lab');
    Permintaan::factory()->for($kunjungan)->create(['dikunci' => true]);
    buatTagihanSinkron($kunjungan, 'lab', 'lunas');
    buatTagihanSinkron($kunjungan, 'farmasi');

    $hasil = app(StatusKunjunganService::class)->sinkron($kunjungan, $user);

    expect($hasil->status)->toBe('farmasi')
        ->and($hasil->riwayatStatus()->count())->toBe(1)
        ->and($hasil->riwayatStatus()->first()->status)->toBe('farmasi');
});

test('sinkron tetap di farmasi saat tagihan farmasi belum lunas', function () {
    $user = User::factory()->create();
    $kunjungan = buatKunjunganSinkron($user, 'farmasi');
    buatTagihanSinkron($kunjungan, 'farmasi');

    $hasil = app(StatusKunjunganService::class)->sinkron($kunjungan, $user);

    expect($hasil->status)->toBe('farmasi')
        ->and(KunjunganRiwayatStatus::count())->toBe(0);
});

test('sinkron menahan selesai saat tagihan farmasi lunas tetapi lab belum lunas', function () {
    $user = User::factory()->create();
    $kunjungan = buatKunjunganSinkron($user, 'farmasi');
    Permintaan::factory()->for($kunjungan)->create(['dikunci' => true]);
    buatTagihanSinkron($kunjungan, 'farmasi', 'lunas');
    buatTagihanSinkron($kunjungan, 'lab');

    $hasil = app(StatusKunjunganService::class)->sinkron($kunjungan, $user);

    expect($hasil->status)->toBe('lab');
});

test('sinkron menahan selesai saat tagihan lab lunas tetapi farmasi belum lunas', function () {
    $user = User::factory()->create();
    $kunjungan = buatKunjunganSinkron($user, 'lab');
    Permintaan::factory()->for($kunjungan)->create(['dikunci' => true]);
    buatTagihanSinkron($kunjungan, 'lab', 'lunas');
    buatTagihanSinkron($kunjungan, 'farmasi');

    $hasil = app(StatusKunjunganService::class)->sinkron($kunjungan, $user);

    expect($hasil->status)->toBe('farmasi');
});

test('sinkron memindahkan ke selesai saat tagihan lab dan farmasi lunas', function () {
    $user = User::factory()->create();
    $kunjungan = buatKunjunganSinkron($user, 'farmasi');
    Permintaan::factory()->for($kunjungan)->create(['dikunci' => true]);
    buatTagihanSinkron($kunjungan, 'lab', 'lunas');
    buatTagihanSinkron($kunjungan, 'farmasi', 'lunas');

    $hasil = app(StatusKunjunganService::class)->sinkron($kunjungan, $user);

    expect($hasil->status)->toBe('selesai')
        ->and($hasil->riwayatStatus()->count())->toBe(1)
        ->and($hasil->riwayatStatus()->first()->status)->toBe('selesai');
});

test('sinkron lab mandiri menunggu tagihan lab lunas', function () {
    $user = User::factory()->create();
    $kunjungan = buatKunjunganSinkron($user, 'lab', 'lab_mandiri');
    Permintaan::factory()->for($kunjungan)->create(['dikunci' => true]);
    $tagihan = buatTagihanSinkron($kunjungan, 'lab');

    $layanan = app(StatusKunjunganService::class);
    $belum = $layanan->sinkron($kunjungan, $user);

    expect($belum->status)->toBe('lab')
        ->and(KunjunganRiwayatStatus::count())->toBe(0);

    $tagihan->update(['status' => 'lunas']);
    $selesai = $layanan->sinkron($belum, $user);

    expect($selesai->status)->toBe('selesai')
        ->and(KunjunganRiwayatStatus::count())->toBe(1);
});

test('sinkron lab mandiri tanpa tagihan langsung selesai', function () {
    $user = User::factory()->create();
    $kunjungan = buatKunjunganSinkron($user, 'lab', 'lab_mandiri');

    $hasil = app(StatusKunjunganService::class)->sinkron($kunjungan, $user);

    expect($hasil->status)->toBe('selesai');
});

test('sinkron apotek umum memindahkan ke farmasi lalu selesai', function () {
    $user = User::factory()->create();
    $kunjungan = buatKunjunganSinkron($user, 'terdaftar', 'apotek_umum');
    $tagihan = buatTagihanSinkron($kunjungan, 'farmasi');

    $layanan = app(StatusKunjunganService::class);
    $farmasi = $layanan->sinkron($kunjungan, $user);

    expect($farmasi->status)->toBe('farmasi');

    $tagihan->update(['status' => 'lunas']);
    $selesai = $layanan->sinkron($farmasi, $user);

    expect($selesai->status)->toBe('selesai');
});

test('sinkron idempoten pada kunjungan yang sudah selesai', function () {
    $user = User::factory()->create();
    $kunjungan = buatKunjunganSinkron($user, 'selesai');

    $hasil = app(StatusKunjunganService::class)->sinkron($kunjungan, $user);

    expect($hasil->status)->toBe('selesai')
        ->and(KunjunganRiwayatStatus::count())->toBe(0);
});

test('sinkron mengikuti urutan lab lalu farmasi lalu selesai', function () {
    $user = User::factory()->create();
    $kunjungan = buatKunjunganSinkron($user);
    $permintaan = Permintaan::factory()->for($kunjungan)->create(['dikunci' => false]);
    $farmasi = buatTagihanSinkron($kunjungan, 'farmasi');

    $layanan = app(StatusKunjunganService::class);

    $setelahLab = $layanan->sinkron($kunjungan, $user);
    expect($setelahLab->status)->toBe('lab');

    $permintaan->update(['dikunci' => true]);
    $lab = buatTagihanSinkron($kunjungan, 'lab');
    $tetapLab = $layanan->sinkron($setelahLab, $user);
    expect($tetapLab->status)->toBe('lab');

    $lab->update(['status' => 'lunas']);
    $setelahFarmasi = $layanan->sinkron($tetapLab, $user);
    expect($setelahFarmasi->status)->toBe('farmasi');

    $farmasi->update(['status' => 'lunas']);
    $selesai = $layanan->sinkron($setelahFarmasi, $user);
    expect($selesai->status)->toBe('selesai');
});
