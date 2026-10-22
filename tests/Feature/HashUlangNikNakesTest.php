<?php

use App\Models\Master\Nakes;
use App\Services\NikHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('perintah hash ulang memperbarui nik_hash nakes', function () {
    $nakes = Nakes::create([
        'nama' => 'Ns. Rina',
        'jenis' => 'perawat',
        'nik' => '3201234567890001',
    ]);

    DB::table('mtr_nakes')->where('id', $nakes->id)->update([
        'nik_hash' => hash_hmac('sha256', '3201234567890001', 'kunci-lama'),
    ]);

    $this->artisan('nakes:hash-ulang-nik')->assertSuccessful();

    expect(Nakes::find($nakes->id)->nik_hash)
        ->toBe(NikHasher::hash('3201234567890001'));
});

test('perintah hash ulang ikut memproses nakes yang sudah dihapus', function () {
    $nakes = Nakes::create([
        'nama' => 'Ns. Lama',
        'jenis' => 'perawat',
        'nik' => '3201234567890002',
    ]);
    $nakes->delete();

    DB::table('mtr_nakes')->where('id', $nakes->id)->update([
        'nik_hash' => hash_hmac('sha256', '3201234567890002', 'kunci-lama'),
    ]);

    $this->artisan('nakes:hash-ulang-nik')->assertSuccessful();

    expect(Nakes::withTrashed()->find($nakes->id)->nik_hash)
        ->toBe(NikHasher::hash('3201234567890002'));
});
