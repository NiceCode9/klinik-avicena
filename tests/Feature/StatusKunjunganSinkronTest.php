<?php

use App\Models\Erm\Pemeriksaan;
use App\Models\Erm\Resep;
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

function buatResepSinkron(Kunjungan $kunjungan, string $status): Resep
{
    $pemeriksaan = Pemeriksaan::factory()->for($kunjungan)->create();

    return Resep::factory()->for($pemeriksaan, 'pemeriksaan')->create(['status' => $status]);
}

test('sinkron memindahkan kunjungan diperiksa tanpa lab dan resep ke selesai', function () {
    $user = User::factory()->create();
    $kunjungan = buatKunjunganSinkron($user);

    $hasil = app(StatusKunjunganService::class)->sinkron($kunjungan, $user);

    expect($hasil->status)->toBe('selesai')
        ->and($hasil->riwayatStatus()->count())->toBe(1)
        ->and($hasil->riwayatStatus()->first()->status)->toBe('selesai');
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

test('sinkron tetap di lab saat satu dari dua permintaan lab belum dikunci', function () {
    $user = User::factory()->create();
    $kunjungan = buatKunjunganSinkron($user);
    Permintaan::factory()->for($kunjungan)->create(['dikunci' => true]);
    Permintaan::factory()->for($kunjungan)->create(['dikunci' => false]);

    $hasil = app(StatusKunjunganService::class)->sinkron($kunjungan, $user);

    expect($hasil->status)->toBe('lab');
});

test('sinkron memindahkan ke selesai saat semua permintaan lab terkunci', function () {
    $user = User::factory()->create();
    $kunjungan = buatKunjunganSinkron($user);
    Permintaan::factory()->for($kunjungan)->create(['dikunci' => true]);
    Permintaan::factory()->for($kunjungan)->create(['dikunci' => true]);

    $hasil = app(StatusKunjunganService::class)->sinkron($kunjungan, $user);

    expect($hasil->status)->toBe('selesai');
});

test('sinkron mensyaratkan permintaan lab batal tetap terkunci sebelum selesai', function () {
    $user = User::factory()->create();
    $kunjungan = buatKunjunganSinkron($user);
    $permintaan = Permintaan::factory()->for($kunjungan)->create([
        'status' => 'batal',
        'dikunci' => false,
    ]);

    $layanan = app(StatusKunjunganService::class);
    $belum = $layanan->sinkron($kunjungan, $user);

    expect($belum->status)->toBe('lab');

    $permintaan->update(['dikunci' => true]);
    $selesai = $layanan->sinkron($belum, $user);

    expect($selesai->status)->toBe('selesai');
});

test('sinkron memindahkan ke farmasi saat resep belum diserahkan', function () {
    $user = User::factory()->create();
    $kunjungan = buatKunjunganSinkron($user);
    buatResepSinkron($kunjungan, 'diterbitkan');

    $hasil = app(StatusKunjunganService::class)->sinkron($kunjungan, $user);

    expect($hasil->status)->toBe('farmasi')
        ->and($hasil->riwayatStatus()->count())->toBe(1)
        ->and($hasil->riwayatStatus()->first()->status)->toBe('farmasi');
});

test('sinkron menganggap resep selesai saat berstatus diserahkan atau batal', function (string $statusResep) {
    $user = User::factory()->create();
    $kunjungan = buatKunjunganSinkron($user);
    buatResepSinkron($kunjungan, $statusResep);

    $hasil = app(StatusKunjunganService::class)->sinkron($kunjungan, $user);

    expect($hasil->status)->toBe('selesai');
})->with(['diserahkan', 'batal']);

test('sinkron memprioritaskan lab saat resep sudah diserahkan tetapi lab belum dikunci', function () {
    $user = User::factory()->create();
    $kunjungan = buatKunjunganSinkron($user);
    Permintaan::factory()->for($kunjungan)->create(['dikunci' => false]);
    buatResepSinkron($kunjungan, 'diserahkan');

    $hasil = app(StatusKunjunganService::class)->sinkron($kunjungan, $user);

    expect($hasil->status)->toBe('lab');
});

test('sinkron memprioritaskan lab saat lab dan resep sama-sama belum selesai', function () {
    $user = User::factory()->create();
    $kunjungan = buatKunjunganSinkron($user);
    Permintaan::factory()->for($kunjungan)->create(['dikunci' => false]);
    buatResepSinkron($kunjungan, 'diterbitkan');

    $hasil = app(StatusKunjunganService::class)->sinkron($kunjungan, $user);

    expect($hasil->status)->toBe('lab');
});

test('sinkron memindahkan ke farmasi saat lab terkunci dan resep belum diserahkan', function () {
    $user = User::factory()->create();
    $kunjungan = buatKunjunganSinkron($user);
    Permintaan::factory()->for($kunjungan)->create(['dikunci' => true]);
    buatResepSinkron($kunjungan, 'diterbitkan');

    $hasil = app(StatusKunjunganService::class)->sinkron($kunjungan, $user);

    expect($hasil->status)->toBe('farmasi');
});

test('sinkron tidak memundurkan kunjungan farmasi saat lab belum dikunci', function () {
    $user = User::factory()->create();
    $kunjungan = buatKunjunganSinkron($user, 'farmasi');
    Permintaan::factory()->for($kunjungan)->create(['dikunci' => false]);

    $hasil = app(StatusKunjunganService::class)->sinkron($kunjungan, $user);

    expect($hasil->status)->toBe('farmasi')
        ->and(KunjunganRiwayatStatus::count())->toBe(0);
});

test('sinkron tidak mengubah kunjungan yang masih menunggu', function () {
    $user = User::factory()->create();
    $kunjungan = buatKunjunganSinkron($user, 'menunggu');

    $hasil = app(StatusKunjunganService::class)->sinkron($kunjungan, $user);

    expect($hasil->status)->toBe('menunggu')
        ->and(KunjunganRiwayatStatus::count())->toBe(0);
});

test('sinkron idempoten pada kunjungan yang sudah di selesai', function () {
    $user = User::factory()->create();
    $kunjungan = buatKunjunganSinkron($user, 'selesai');

    $hasil = app(StatusKunjunganService::class)->sinkron($kunjungan, $user);

    expect($hasil->status)->toBe('selesai')
        ->and(KunjunganRiwayatStatus::count())->toBe(0);
});

test('sinkron lab mandiri menunggu semua permintaan lab terkunci', function () {
    $user = User::factory()->create();
    $kunjungan = buatKunjunganSinkron($user, 'lab', 'lab_mandiri');
    $permintaan = Permintaan::factory()->for($kunjungan)->create(['dikunci' => false]);

    $layanan = app(StatusKunjunganService::class);
    $belum = $layanan->sinkron($kunjungan, $user);

    expect($belum->status)->toBe('lab')
        ->and(KunjunganRiwayatStatus::count())->toBe(0);

    $permintaan->update(['dikunci' => true]);
    $selesai = $layanan->sinkron($belum, $user);

    expect($selesai->status)->toBe('selesai')
        ->and(KunjunganRiwayatStatus::count())->toBe(1);
});

test('sinkron lab mandiri tanpa permintaan lab langsung ke selesai', function () {
    $user = User::factory()->create();
    $kunjungan = buatKunjunganSinkron($user, 'lab', 'lab_mandiri');

    $hasil = app(StatusKunjunganService::class)->sinkron($kunjungan, $user);

    expect($hasil->status)->toBe('selesai');
});

test('sinkron mengikuti urutan lab lalu farmasi lalu selesai', function () {
    $user = User::factory()->create();
    $kunjungan = buatKunjunganSinkron($user);
    $permintaan = Permintaan::factory()->for($kunjungan)->create(['dikunci' => false]);
    $resep = buatResepSinkron($kunjungan, 'diterbitkan');

    $layanan = app(StatusKunjunganService::class);

    $setelahLab = $layanan->sinkron($kunjungan, $user);
    expect($setelahLab->status)->toBe('lab');

    $permintaan->update(['dikunci' => true]);
    $setelahFarmasi = $layanan->sinkron($setelahLab, $user);
    expect($setelahFarmasi->status)->toBe('farmasi');

    $resep->update(['status' => 'diserahkan']);
    $selesai = $layanan->sinkron($setelahFarmasi, $user);
    expect($selesai->status)->toBe('selesai');
});
