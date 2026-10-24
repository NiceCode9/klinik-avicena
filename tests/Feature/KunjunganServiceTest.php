<?php

use App\Models\Master\Pasien;
use App\Models\Master\Poli;
use App\Models\Registrasi\Antrean;
use App\Models\Registrasi\Kunjungan;
use App\Models\Registrasi\Ttv;
use App\Models\Sys\LogAktivitas;
use App\Models\Sys\Pengaturan;
use App\Models\User;
use App\Services\Registrasi\AntreanService;
use App\Services\Registrasi\Exceptions\StatusTidakValidException;
use App\Services\Registrasi\KunjunganService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

afterEach(function () {
    $this->travelBack();
});

function dataTtvLengkap(array $ganti = []): array
{
    return $ganti + [
        'tekanan_darah_sistolik' => 120,
        'tekanan_darah_diastolik' => 80,
        'nadi' => 80,
        'suhu' => 36.5,
        'berat_badan' => 60.5,
    ];
}

test('nomor kunjungan berurutan pada hari yang sama dan reset pada hari berikutnya', function () {
    $this->travelTo('2026-10-23 08:00:00');

    $layanan = app(KunjunganService::class);
    $pasien = Pasien::factory()->create();
    $user = User::factory()->create();

    $pertama = $layanan->buatKunjunganPoli($pasien, $user);
    $kedua = $layanan->buatKunjunganPoli($pasien, $user);

    $this->travelTo('2026-10-24 08:00:00');
    $ketiga = $layanan->buatKunjunganPoli($pasien, $user);

    expect($pertama->no_kunjungan)->toBe('KJ-20261023-0001')
        ->and($kedua->no_kunjungan)->toBe('KJ-20261023-0002')
        ->and($ketiga->no_kunjungan)->toBe('KJ-20261024-0001')
        ->and(Kunjungan::count())->toBe(3)
        ->and(Pengaturan::where('kunci', 'no_kunjungan_terakhir')->value('nilai'))->toBe('20261024:1');
});

test('kunjungan baru mengisi waktu datang dan riwayat status awal', function () {
    $this->travelTo('2026-10-23 08:15:00');

    $layanan = app(KunjunganService::class);
    $pasien = Pasien::factory()->create();
    $user = User::factory()->create();

    $kunjungan = $layanan->buatKunjunganPoli($pasien, $user);

    expect($kunjungan->status)->toBe('terdaftar')
        ->and($kunjungan->jenis_kunjungan)->toBe('poli')
        ->and($kunjungan->tanggal_kunjungan->toDateString())->toBe('2026-10-23')
        ->and($kunjungan->waktu_datang->toDateTimeString())->toBe('2026-10-23 08:15:00')
        ->and($kunjungan->waktu_mulai)->toBeNull()
        ->and($kunjungan->waktu_selesai)->toBeNull()
        ->and($kunjungan->dibuat_oleh)->toBe($user->id);

    $riwayat = $kunjungan->riwayatStatus()->get();

    expect($riwayat)->toHaveCount(1)
        ->and($riwayat->first()->status)->toBe('terdaftar')
        ->and($riwayat->first()->dibuat_oleh)->toBe($user->id)
        ->and($riwayat->first()->waktu->toDateTimeString())->toBe('2026-10-23 08:15:00');
});

test('simpanTtv menyimpan TTV, membuat antrean, dan mengubah status ke menunggu', function () {
    $this->travelTo('2026-10-23 08:00:00');

    $layanan = app(KunjunganService::class);
    $pasien = Pasien::factory()->create();
    $user = User::factory()->create();
    $poli = Poli::factory()->create(['kode' => 'INT', 'nama' => 'Poli Dalam']);
    $kunjungan = $layanan->buatKunjunganPoli($pasien, $user);

    $this->travelTo('2026-10-23 08:10:00');

    $ttv = $layanan->simpanTtv(
        $kunjungan,
        $poli,
        dataTtvLengkap(['tinggi_badan' => 170.5, 'saturasi_oksigen' => 98]),
        $user,
    );

    $kunjungan->refresh();
    $antrean = $kunjungan->antrean()->first();

    expect($ttv->kunjungan_id)->toBe($kunjungan->id)
        ->and($ttv->perawat_id)->toBe($user->id)
        ->and($ttv->tekanan_darah_sistolik)->toBe(120)
        ->and($ttv->tekanan_darah_diastolik)->toBe(80)
        ->and($ttv->nadi)->toBe(80)
        ->and((float) $ttv->tinggi_badan)->toBe(170.5)
        ->and((int) $ttv->saturasi_oksigen)->toBe(98)
        ->and($kunjungan->status)->toBe('menunggu')
        ->and($kunjungan->waktu_datang->toDateTimeString())->toBe('2026-10-23 08:00:00')
        ->and($kunjungan->waktu_mulai)->toBeNull()
        ->and($kunjungan->waktu_selesai)->toBeNull()
        ->and($antrean)->not->toBeNull()
        ->and($antrean->nomor_antrean)->toBe('INT-001')
        ->and($antrean->status)->toBe('menunggu')
        ->and($kunjungan->riwayatStatus()->pluck('status')->all())->toBe(['terdaftar', 'ttv', 'menunggu'])
        ->and(Ttv::count())->toBe(1);
});

test('simpanTtv menolak TTV yang kehilangan field wajib', function (string $field) {
    $layanan = app(KunjunganService::class);
    $pasien = Pasien::factory()->create();
    $user = User::factory()->create();
    $poli = Poli::factory()->create(['kode' => 'INT']);
    $kunjungan = $layanan->buatKunjunganPoli($pasien, $user);
    $data = dataTtvLengkap();
    unset($data[$field]);

    expect(fn () => $layanan->simpanTtv($kunjungan, $poli, $data, $user))
        ->toThrow(ValidationException::class);

    expect($kunjungan->fresh()->status)->toBe('terdaftar')
        ->and(Ttv::count())->toBe(0)
        ->and(Antrean::count())->toBe(0);
})->with([
    'sistolik' => ['tekanan_darah_sistolik'],
    'diastolik' => ['tekanan_darah_diastolik'],
    'nadi' => ['nadi'],
    'suhu' => ['suhu'],
    'berat badan' => ['berat_badan'],
]);

test('TTV kedua untuk kunjungan yang sama ditolak', function () {
    $layanan = app(KunjunganService::class);
    $pasien = Pasien::factory()->create();
    $user = User::factory()->create();
    $poli = Poli::factory()->create(['kode' => 'INT']);
    $kunjungan = $layanan->buatKunjunganPoli($pasien, $user);

    $layanan->simpanTtv($kunjungan, $poli, dataTtvLengkap(), $user);

    expect(fn () => $layanan->simpanTtv($kunjungan, $poli, dataTtvLengkap(), $user))
        ->toThrow(StatusTidakValidException::class);

    expect(Ttv::count())->toBe(1)
        ->and(Antrean::count())->toBe(1);
});

test('simpanTtv menolak kunjungan yang statusnya bukan terdaftar', function () {
    $layanan = app(KunjunganService::class);
    $user = User::factory()->create();
    $poli = Poli::factory()->create(['kode' => 'INT']);
    $kunjungan = Kunjungan::factory()->create([
        'jenis_kunjungan' => 'poli',
        'status' => 'menunggu',
        'dibuat_oleh' => $user->id,
    ]);

    expect(fn () => $layanan->simpanTtv($kunjungan, $poli, dataTtvLengkap(), $user))
        ->toThrow(StatusTidakValidException::class);

    expect(Ttv::count())->toBe(0)
        ->and(Antrean::count())->toBe(0);
});

test('simpanTtv menolak kunjungan non-poli', function () {
    $layanan = app(KunjunganService::class);
    $user = User::factory()->create();
    $poli = Poli::factory()->create(['kode' => 'INT']);
    $kunjungan = Kunjungan::factory()->create([
        'jenis_kunjungan' => 'lab_mandiri',
        'status' => 'terdaftar',
        'dibuat_oleh' => $user->id,
    ]);

    expect(fn () => $layanan->simpanTtv($kunjungan, $poli, dataTtvLengkap(), $user))
        ->toThrow(StatusTidakValidException::class);

    expect(Ttv::count())->toBe(0);
});

test('simpanTtv menolak nilai yang berada di luar rentang wajar', function (string $field, int|float $nilai) {
    $layanan = app(KunjunganService::class);
    $user = User::factory()->create();
    $poli = Poli::factory()->create(['kode' => 'INT']);
    $kunjungan = $layanan->buatKunjunganPoli(Pasien::factory()->create(), $user);

    expect(fn () => $layanan->simpanTtv($kunjungan, $poli, dataTtvLengkap([$field => $nilai]), $user))
        ->toThrow(ValidationException::class);

    expect(Ttv::count())->toBe(0)
        ->and($kunjungan->fresh()->status)->toBe('terdaftar')
        ->and(Antrean::count())->toBe(0);
})->with([
    'sistolik terlalu tinggi' => ['tekanan_darah_sistolik', 301],
    'sistolik terlalu rendah' => ['tekanan_darah_sistolik', 49],
    'diastolik terlalu tinggi' => ['tekanan_darah_diastolik', 201],
    'diastolik terlalu rendah' => ['tekanan_darah_diastolik', 19],
    'nadi terlalu tinggi' => ['nadi', 301],
    'nadi terlalu rendah' => ['nadi', 19],
    'suhu terlalu tinggi' => ['suhu', 45.1],
    'suhu terlalu rendah' => ['suhu', 24.9],
    'berat badan nol' => ['berat_badan', 0],
    'berat badan terlalu besar' => ['berat_badan', 501],
    'tinggi badan terlalu rendah' => ['tinggi_badan', 19],
    'saturasi lebih dari 100' => ['saturasi_oksigen', 101],
]);

test('kegagalan membuat antrean membatalkan TTV dan status kunjungan', function () {
    $user = User::factory()->create();
    $poli = Poli::factory()->create(['kode' => 'INT']);
    $kunjungan = app(KunjunganService::class)->buatKunjunganPoli(Pasien::factory()->create(), $user);

    $this->mock(AntreanService::class)
        ->shouldReceive('ambilNomor')
        ->andThrow(new RuntimeException('antrean gagal'));

    $layanan = app(KunjunganService::class);

    expect(fn () => $layanan->simpanTtv($kunjungan, $poli, dataTtvLengkap(), $user))
        ->toThrow(RuntimeException::class);

    expect(Ttv::count())->toBe(0)
        ->and(Antrean::count())->toBe(0)
        ->and($kunjungan->fresh()->status)->toBe('terdaftar')
        ->and($kunjungan->riwayatStatus()->where('status', 'ttv')->count())->toBe(0)
        ->and($kunjungan->riwayatStatus()->where('status', 'menunggu')->count())->toBe(0);
});

test('pendaftaran kunjungan dan penyimpanan TTV tercatat di log aktivitas', function () {
    $layanan = app(KunjunganService::class);
    $user = User::factory()->create();
    $poli = Poli::factory()->create(['kode' => 'INT']);
    $kunjungan = $layanan->buatKunjunganPoli(Pasien::factory()->create(), $user);

    expect(LogAktivitas::where('aksi', 'tambah_kunjungan')->where('data_id', $kunjungan->id)->exists())->toBeTrue();

    $ttv = $layanan->simpanTtv($kunjungan, $poli, dataTtvLengkap(), $user);

    expect(LogAktivitas::where('aksi', 'simpan_ttv')->where('data_id', $ttv->id)->exists())->toBeTrue()
        ->and(LogAktivitas::where('aksi', 'ubah_status_kunjungan')->where('data_id', $kunjungan->id)->count())->toBe(2);
});
