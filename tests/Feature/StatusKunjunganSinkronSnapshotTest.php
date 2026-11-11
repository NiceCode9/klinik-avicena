<?php

use App\Models\Billing\Tagihan;
use App\Models\Lab\Permintaan;
use App\Models\Registrasi\Kunjungan;
use App\Models\User;
use App\Services\Registrasi\StatusKunjunganService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;

uses(DatabaseMigrations::class);

beforeEach(function () {
    config(['database.connections.mysql_sinkron' => config('database.connections.mysql')]);
    DB::purge('mysql_sinkron');
});

function buatTagihanSnapshot(Kunjungan $kunjungan, string $jenis, string $status = 'belum_bayar'): Tagihan
{
    return Tagihan::create([
        'no_tagihan' => 'TG-'.fake()->unique()->numerify('##########'),
        'kunjungan_id' => $kunjungan->id,
        'jenis' => $jenis,
        'subtotal' => '10000.00',
        'diskon' => '0',
        'total' => '10000.00',
        'status' => $status,
    ]);
}

test('sinkron memakai data lab terbaru meski transaksi pemanggil punya snapshot lama', function () {
    $user = User::factory()->create();
    $kunjungan = Kunjungan::factory()->create([
        'jenis_kunjungan' => 'poli',
        'status' => 'diperiksa',
        'dibuat_oleh' => $user->id,
    ]);
    $permintaan = Permintaan::factory()->for($kunjungan)->create(['dikunci' => false]);
    buatTagihanSnapshot($kunjungan, 'farmasi');

    $koneksiLain = DB::connection('mysql_sinkron');
    $koneksiLain->getPdo();

    DB::beginTransaction();
    Kunjungan::whereKey($kunjungan->id)->first();

    $koneksiLain->table('lab_permintaan')->where('id', $permintaan->id)->update(['dikunci' => true]);

    $hasil = app(StatusKunjunganService::class)->sinkron($kunjungan, $user);

    expect($hasil->status)->toBe('farmasi');

    DB::rollBack();
    DB::purge('mysql_sinkron');
});

test('sinkron memakai status tagihan terbaru meski transaksi pemanggil punya snapshot lama', function () {
    $user = User::factory()->create();
    $kunjungan = Kunjungan::factory()->create([
        'jenis_kunjungan' => 'poli',
        'status' => 'farmasi',
        'dibuat_oleh' => $user->id,
    ]);
    $tagihan = buatTagihanSnapshot($kunjungan, 'farmasi');

    $koneksiLain = DB::connection('mysql_sinkron');
    $koneksiLain->getPdo();

    DB::beginTransaction();
    Kunjungan::whereKey($kunjungan->id)->first();

    $koneksiLain->table('bil_tagihan')->where('id', $tagihan->id)->update(['status' => 'lunas']);

    $hasil = app(StatusKunjunganService::class)->sinkron($kunjungan, $user);

    expect($hasil->status)->toBe('selesai');

    DB::rollBack();
    DB::purge('mysql_sinkron');
});
