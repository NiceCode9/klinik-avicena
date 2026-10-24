<?php

use App\Events\KunjunganDidaftarkan;
use App\Events\TtvDisimpan;
use App\Models\Master\Pasien;
use App\Models\Master\Poli;
use App\Models\Registrasi\Kunjungan;
use App\Models\Registrasi\Ttv;
use App\Models\User;
use App\Services\Registrasi\KunjunganService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

uses(DatabaseMigrations::class);

function dataTtvEvent(): array
{
    return [
        'tekanan_darah_sistolik' => 120,
        'tekanan_darah_diastolik' => 80,
        'nadi' => 80,
        'suhu' => 36.5,
        'berat_badan' => 60.5,
    ];
}

test('KunjunganDidaftarkan terpancar setelah transaksi commit', function () {
    $terpancar = 0;
    Event::listen(KunjunganDidaftarkan::class, function () use (&$terpancar) {
        $terpancar++;
    });

    app(KunjunganService::class)->buatKunjunganPoli(
        Pasien::factory()->create(),
        User::factory()->create(),
    );

    expect($terpancar)->toBe(1);
});

test('KunjunganDidaftarkan tidak terpancar saat transaksi dibatalkan', function () {
    $terpancar = 0;
    Event::listen(KunjunganDidaftarkan::class, function () use (&$terpancar) {
        $terpancar++;
    });

    DB::beginTransaction();

    app(KunjunganService::class)->buatKunjunganPoli(
        Pasien::factory()->create(),
        User::factory()->create(),
    );

    expect($terpancar)->toBe(0);

    DB::rollBack();

    expect($terpancar)->toBe(0)
        ->and(Kunjungan::count())->toBe(0);
});

test('TtvDisimpan terpancar setelah transaksi commit', function () {
    $user = User::factory()->create();
    $poli = Poli::factory()->create(['kode' => 'INT']);
    $kunjungan = app(KunjunganService::class)->buatKunjunganPoli(
        Pasien::factory()->create(),
        $user,
    );

    $terpancar = 0;
    Event::listen(TtvDisimpan::class, function () use (&$terpancar) {
        $terpancar++;
    });

    app(KunjunganService::class)->simpanTtv($kunjungan, $poli, dataTtvEvent(), $user);

    expect($terpancar)->toBe(1);
});

test('TtvDisimpan tidak terpancar saat transaksi dibatalkan', function () {
    $user = User::factory()->create();
    $poli = Poli::factory()->create(['kode' => 'INT']);
    $kunjungan = app(KunjunganService::class)->buatKunjunganPoli(
        Pasien::factory()->create(),
        $user,
    );

    $terpancar = 0;
    Event::listen(TtvDisimpan::class, function () use (&$terpancar) {
        $terpancar++;
    });

    DB::beginTransaction();

    app(KunjunganService::class)->simpanTtv($kunjungan, $poli, dataTtvEvent(), $user);

    expect($terpancar)->toBe(0);

    DB::rollBack();

    expect($terpancar)->toBe(0)
        ->and(Ttv::count())->toBe(0)
        ->and(Kunjungan::whereKey($kunjungan->id)->value('status'))->toBe('terdaftar');
});
