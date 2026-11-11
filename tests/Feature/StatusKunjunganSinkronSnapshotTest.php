<?php

use App\Models\Erm\Pemeriksaan;
use App\Models\Erm\Resep;
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

test('sinkron memakai data lab terbaru meski transaksi pemanggil punya snapshot lama', function () {
    $user = User::factory()->create();
    $kunjungan = Kunjungan::factory()->create([
        'jenis_kunjungan' => 'poli',
        'status' => 'diperiksa',
        'dibuat_oleh' => $user->id,
    ]);
    $permintaan = Permintaan::factory()->for($kunjungan)->create(['dikunci' => false]);

    $koneksiLain = DB::connection('mysql_sinkron');
    $koneksiLain->getPdo();

    DB::beginTransaction();
    Kunjungan::whereKey($kunjungan->id)->first();

    $koneksiLain->table('lab_permintaan')->where('id', $permintaan->id)->update(['dikunci' => true]);

    $hasil = app(StatusKunjunganService::class)->sinkron($kunjungan, $user);

    expect($hasil->status)->toBe('selesai');

    DB::rollBack();
    DB::purge('mysql_sinkron');
});

test('sinkron memakai status resep terbaru meski transaksi pemanggil punya snapshot lama', function () {
    $user = User::factory()->create();
    $kunjungan = Kunjungan::factory()->create([
        'jenis_kunjungan' => 'poli',
        'status' => 'lab',
        'dibuat_oleh' => $user->id,
    ]);
    Permintaan::factory()->for($kunjungan)->create(['dikunci' => true]);
    $pemeriksaan = Pemeriksaan::factory()->for($kunjungan)->create();
    $resep = Resep::factory()->for($pemeriksaan, 'pemeriksaan')->create(['status' => 'diterbitkan']);

    $koneksiLain = DB::connection('mysql_sinkron');
    $koneksiLain->getPdo();

    DB::beginTransaction();
    Kunjungan::whereKey($kunjungan->id)->first();

    $koneksiLain->table('erm_resep')->where('id', $resep->id)->update(['status' => 'diserahkan']);

    $hasil = app(StatusKunjunganService::class)->sinkron($kunjungan, $user);

    expect($hasil->status)->toBe('selesai');

    DB::rollBack();
    DB::purge('mysql_sinkron');
});
