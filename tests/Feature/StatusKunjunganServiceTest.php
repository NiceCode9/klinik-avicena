<?php

use App\Models\Registrasi\Kunjungan;
use App\Models\Registrasi\KunjunganRiwayatStatus;
use App\Models\Sys\LogAktivitas;
use App\Models\User;
use App\Services\Registrasi\Exceptions\StatusTidakValidException;
use App\Services\Registrasi\StatusKunjunganService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function matriksTransisiKunjungan(): array
{
    return [
        'poli' => [
            'terdaftar' => ['ttv', 'batal'],
            'ttv' => ['menunggu', 'batal'],
            'menunggu' => ['diperiksa', 'batal'],
            'diperiksa' => ['lab', 'farmasi', 'batal'],
            'lab' => ['farmasi', 'selesai', 'batal'],
            'farmasi' => ['lab', 'selesai', 'batal'],
            'selesai' => [],
            'batal' => [],
        ],
        'lab_mandiri' => [
            'terdaftar' => ['lab', 'batal'],
            'ttv' => [],
            'menunggu' => [],
            'diperiksa' => [],
            'lab' => ['selesai', 'batal'],
            'farmasi' => [],
            'selesai' => [],
            'batal' => [],
        ],
        'apotek_umum' => [
            'terdaftar' => ['farmasi', 'batal'],
            'ttv' => [],
            'menunggu' => [],
            'diperiksa' => [],
            'lab' => [],
            'farmasi' => ['selesai', 'batal'],
            'selesai' => [],
            'batal' => [],
        ],
    ];
}

dataset('transisi_kunjungan_sah', function () {
    foreach (matriksTransisiKunjungan() as $jenis => $statusAwal) {
        foreach ($statusAwal as $asal => $tujuan) {
            foreach ($tujuan as $target) {
                yield "{$jenis}: {$asal} -> {$target}" => [$jenis, $asal, $target];
            }
        }
    }
});

dataset('transisi_kunjungan_tidak_sah', function () {
    $semuaStatus = ['terdaftar', 'ttv', 'menunggu', 'diperiksa', 'lab', 'farmasi', 'selesai', 'batal'];

    foreach (matriksTransisiKunjungan() as $jenis => $statusAwal) {
        foreach ($semuaStatus as $asal) {
            $tujuanSah = $statusAwal[$asal] ?? [];

            foreach ($semuaStatus as $target) {
                if (! in_array($target, $tujuanSah, true)) {
                    yield "{$jenis}: {$asal} -> {$target}" => [$jenis, $asal, $target];
                }
            }
        }
    }
});

test('transisi status yang sah mengubah status dan menulis riwayat', function (string $jenis, string $asal, string $target) {
    $user = User::factory()->create();
    $kunjungan = Kunjungan::factory()->create([
        'jenis_kunjungan' => $jenis,
        'status' => $asal,
        'dibuat_oleh' => $user->id,
    ]);

    $hasil = app(StatusKunjunganService::class)->pindahkan(
        $kunjungan,
        $target,
        $user,
        $target === 'batal' ? 'Alasan pembatalan' : null,
    );

    $riwayat = KunjunganRiwayatStatus::where('kunjungan_id', $kunjungan->id)->latest('id')->first();

    expect($hasil->status)->toBe($target)
        ->and($hasil->fresh()->status)->toBe($target)
        ->and($riwayat)->not->toBeNull()
        ->and($riwayat->status)->toBe($target)
        ->and($riwayat->dibuat_oleh)->toBe($user->id)
        ->and($riwayat->waktu)->not->toBeNull()
        ->and($riwayat->alasan)->toBe($target === 'batal' ? 'Alasan pembatalan' : null)
        ->and(LogAktivitas::where('aksi', 'ubah_status_kunjungan')
            ->where('data_id', $kunjungan->id)
            ->where('pengguna_id', $user->id)
            ->exists())->toBeTrue();
})->with('transisi_kunjungan_sah');

test('transisi status yang tidak sah ditolak tanpa mengubah data', function (string $jenis, string $asal, string $target) {
    $user = User::factory()->create();
    $kunjungan = Kunjungan::factory()->create([
        'jenis_kunjungan' => $jenis,
        'status' => $asal,
        'dibuat_oleh' => $user->id,
    ]);

    expect(fn () => app(StatusKunjunganService::class)->pindahkan(
        $kunjungan,
        $target,
        $user,
        $target === 'batal' ? 'Alasan pembatalan' : null,
    ))->toThrow(StatusTidakValidException::class);

    expect($kunjungan->fresh()->status)->toBe($asal)
        ->and(KunjunganRiwayatStatus::count())->toBe(0)
        ->and(LogAktivitas::where('aksi', 'ubah_status_kunjungan')->count())->toBe(0);
})->with('transisi_kunjungan_tidak_sah');

test('pembatalan kunjungan menolak alasan kosong', function (?string $alasan) {
    $user = User::factory()->create();
    $kunjungan = Kunjungan::factory()->create([
        'status' => 'terdaftar',
        'dibuat_oleh' => $user->id,
    ]);

    expect(fn () => app(StatusKunjunganService::class)->pindahkan($kunjungan, 'batal', $user, $alasan))
        ->toThrow(StatusTidakValidException::class);

    expect($kunjungan->fresh()->status)->toBe('terdaftar')
        ->and(KunjunganRiwayatStatus::count())->toBe(0);
})->with([null, '', '   ']);

test('status tujuan yang tidak dikenal ditolak', function () {
    $user = User::factory()->create();
    $kunjungan = Kunjungan::factory()->create([
        'status' => 'terdaftar',
        'dibuat_oleh' => $user->id,
    ]);

    expect(fn () => app(StatusKunjunganService::class)->pindahkan($kunjungan, 'tidak_ada', $user))
        ->toThrow(StatusTidakValidException::class);

    expect($kunjungan->fresh()->status)->toBe('terdaftar')
        ->and(KunjunganRiwayatStatus::count())->toBe(0);
});
