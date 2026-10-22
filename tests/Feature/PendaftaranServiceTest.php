<?php

use App\Models\Master\Pasien;
use App\Models\Sys\LogAktivitas;
use App\Models\User;
use App\Services\NikHasher;
use App\Services\Registrasi\Exceptions\NikSudahTerdaftarException;
use App\Services\Registrasi\Exceptions\NikTidakValidException;
use App\Services\Registrasi\PendaftaranService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function dataPasien(array $ganti = []): array
{
    return $ganti + [
        'nik' => '3201234567890001',
        'nama_lengkap' => 'Siti Aminah',
        'tempat_lahir' => 'Bandung',
        'tanggal_lahir' => '1990-01-01',
        'jenis_kelamin' => 'P',
        'golongan_darah' => 'O',
        'alamat' => 'Jl. Merdeka 1',
        'no_telepon' => '081234567890',
        'nama_wali' => 'Ahmad',
    ];
}

test('NikHasher memakai NIK_HMAC_KEY yang dikonfigurasi', function () {
    $nik = '3201234567890001';

    expect(NikHasher::hash($nik))
        ->toBe(hash_hmac('sha256', $nik, config('app.nik_hmac_key')));
});

test('NikHasher gagal keras jika NIK_HMAC_KEY kosong', function () {
    $asli = config('app.nik_hmac_key');
    config(['app.nik_hmac_key' => '']);

    try {
        NikHasher::hash('3201234567890001');
    } finally {
        config(['app.nik_hmac_key' => $asli]);
    }
})->throws(RuntimeException::class);

test('cariByNik mengembalikan pasien berdasarkan nik_hash', function () {
    $pasien = app(PendaftaranService::class)->daftarkanPasienBaru(
        dataPasien(),
        User::factory()->create(),
    );

    expect(app(PendaftaranService::class)->cariByNik('3201234567890001')?->id)
        ->toBe($pasien->id);
});

test('cariByNik mengembalikan null untuk NIK yang belum terdaftar', function () {
    expect(app(PendaftaranService::class)->cariByNik('3201234567899999'))
        ->toBeNull();
});

test('cariByNik menolak NIK yang bukan 16 digit angka', function (string $nik) {
    expect(fn () => app(PendaftaranService::class)->cariByNik($nik))
        ->toThrow(NikTidakValidException::class);
})->with([
    'terlalu pendek' => '123',
    'bukan angka' => 'abcdefghijklmnop',
    'terlalu panjang' => '32012345678900011',
]);

test('daftarkanPasienBaru menyimpan NIK terenkripsi dan hash', function () {
    $pasien = app(PendaftaranService::class)->daftarkanPasienBaru(
        dataPasien(),
        User::factory()->create(),
    );

    expect($pasien->nik)->toBe('3201234567890001')
        ->and($pasien->nik_hash)->toBe(NikHasher::hash('3201234567890001'))
        ->and(DB::table('mtr_pasien')->where('id', $pasien->id)->value('nik'))
        ->not->toBe('3201234567890001');
});

test('nomor rekam medis berurut dan unik', function () {
    $layanan = app(PendaftaranService::class);
    $oleh = User::factory()->create();

    $pertama = $layanan->daftarkanPasienBaru(dataPasien(), $oleh);
    $kedua = $layanan->daftarkanPasienBaru(
        dataPasien(['nik' => '3201234567890002']),
        $oleh,
    );

    expect($pertama->no_rekam_medis)->toBe('RM-000001')
        ->and($kedua->no_rekam_medis)->toBe('RM-000002')
        ->and(Pasien::count())->toBe(2);
});

test('NIK ganda ditolak', function () {
    $layanan = app(PendaftaranService::class);
    $oleh = User::factory()->create();

    $layanan->daftarkanPasienBaru(dataPasien(), $oleh);

    expect(fn () => $layanan->daftarkanPasienBaru(dataPasien(), $oleh))
        ->toThrow(NikSudahTerdaftarException::class);

    expect(Pasien::count())->toBe(1);
});

test('NIK milik pasien yang sudah dihapus tetap ditolak', function () {
    $layanan = app(PendaftaranService::class);
    $oleh = User::factory()->create();

    $pasien = $layanan->daftarkanPasienBaru(dataPasien(), $oleh);
    $pasien->delete();

    expect(fn () => $layanan->daftarkanPasienBaru(dataPasien(), $oleh))
        ->toThrow(NikSudahTerdaftarException::class);
});

test('pendaftaran pasien dicatat di log aktivitas', function () {
    $pasien = app(PendaftaranService::class)->daftarkanPasienBaru(
        dataPasien(),
        User::factory()->create(),
    );

    expect(LogAktivitas::where('aksi', 'tambah_pasien')
        ->where('nama_tabel', 'mtr_pasien')
        ->where('data_id', $pasien->id)
        ->exists())->toBeTrue();
});
