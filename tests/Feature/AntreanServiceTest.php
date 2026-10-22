<?php

use App\Models\Master\Pasien;
use App\Models\Master\Poli;
use App\Models\Registrasi\Antrean;
use App\Models\Registrasi\Kunjungan;
use App\Models\Registrasi\PenghitungAntrean;
use App\Services\Registrasi\AntreanService;
use App\Services\Registrasi\Exceptions\StatusAntreanTidakValidException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

afterEach(function () {
    $this->travelBack();
});

function buatPoliAntrean(string $kode = 'INT', string $nama = 'Poli Umum'): Poli
{
    return Poli::create(['kode' => $kode, 'nama' => $nama]);
}

function buatPasienAntrean(): Pasien
{
    static $urut = 0;
    $urut++;

    return Pasien::create([
        'no_rekam_medis' => 'RM-'.str_pad((string) $urut, 6, '0', STR_PAD_LEFT),
        'nik' => '3201'.str_pad((string) $urut, 12, '0', STR_PAD_LEFT),
        'nama_lengkap' => 'Pasien Antrean '.$urut,
        'tanggal_lahir' => '1990-01-01',
        'jenis_kelamin' => 'P',
        'golongan_darah' => 'O',
    ]);
}

function buatKunjunganAntrean(?Pasien $pasien = null): Kunjungan
{
    return Kunjungan::create([
        'no_kunjungan' => 'KJ-'.Str::random(20),
        'pasien_id' => ($pasien ?? buatPasienAntrean())->id,
        'tanggal_kunjungan' => today(),
        'jenis_kunjungan' => 'poli',
        'status' => 'terdaftar',
    ]);
}

function buatAntreanBerstatus(string $status): Antrean
{
    $layanan = app(AntreanService::class);
    $antrean = $layanan->ambilNomor(buatPoliAntrean(), buatKunjunganAntrean());

    if ($status === 'menunggu') {
        return $antrean;
    }

    $layanan->panggil($antrean);

    if ($status === 'dipanggil') {
        return $antrean->fresh();
    }

    $layanan->layani($antrean);

    if ($status === 'dilayani') {
        return $antrean->fresh();
    }

    $layanan->selesai($antrean);

    if ($status === 'selesai') {
        return $antrean->fresh();
    }

    throw new InvalidArgumentException("Status antrean tidak dikenal: {$status}");
}

test('ambilNomor memberi nomor berurutan untuk poli dan tanggal yang sama', function () {
    $poli = buatPoliAntrean();
    $layanan = app(AntreanService::class);

    $pertama = $layanan->ambilNomor($poli, buatKunjunganAntrean());
    $kedua = $layanan->ambilNomor($poli, buatKunjunganAntrean());

    expect($pertama->nomor_urut)->toBe(1)
        ->and($pertama->nomor_antrean)->toBe('INT-001')
        ->and($pertama->status)->toBe('menunggu')
        ->and($pertama->tanggal->toDateString())->toBe(today()->toDateString())
        ->and($kedua->nomor_urut)->toBe(2)
        ->and($kedua->nomor_antrean)->toBe('INT-002')
        ->and(Antrean::count())->toBe(2)
        ->and(PenghitungAntrean::where('poli_id', $poli->id)
            ->whereDate('tanggal', today())
            ->value('nomor_terakhir'))->toBe(2);
});

test('nomor antrean mulai dari satu lagi pada hari berikutnya', function () {
    $poli = buatPoliAntrean();
    $layanan = app(AntreanService::class);

    $this->travelTo('2026-10-24 09:00:00');
    $pertama = $layanan->ambilNomor($poli, buatKunjunganAntrean());

    $this->travelTo('2026-10-25 09:00:00');
    $kedua = $layanan->ambilNomor($poli, buatKunjunganAntrean());

    expect($pertama->nomor_antrean)->toBe('INT-001')
        ->and($kedua->nomor_antrean)->toBe('INT-001')
        ->and($pertama->tanggal->toDateString())->toBe('2026-10-24')
        ->and($kedua->tanggal->toDateString())->toBe('2026-10-25')
        ->and(PenghitungAntrean::count())->toBe(2);
});

test('poli berbeda memiliki urutan antrean terpisah', function () {
    $poliDalam = buatPoliAntrean('INT', 'Poli Dalam');
    $poliGigi = buatPoliAntrean('GIG', 'Poli Gigi');
    $layanan = app(AntreanService::class);

    $dalam = $layanan->ambilNomor($poliDalam, buatKunjunganAntrean());
    $gigi = $layanan->ambilNomor($poliGigi, buatKunjunganAntrean());

    expect($dalam->nomor_antrean)->toBe('INT-001')
        ->and($gigi->nomor_antrean)->toBe('GIG-001')
        ->and(PenghitungAntrean::count())->toBe(2);
});

test('unique index menolak nomor urut ganda pada poli dan tanggal yang sama', function () {
    $poli = buatPoliAntrean();
    $kunjungan = buatKunjunganAntrean();
    $antrean = app(AntreanService::class)->ambilNomor($poli, $kunjungan);

    expect(fn () => DB::table('reg_antrean')->insert([
        'kunjungan_id' => $kunjungan->id,
        'poli_id' => $poli->id,
        'tanggal' => $antrean->tanggal->toDateString(),
        'nomor_urut' => $antrean->nomor_urut,
        'nomor_antrean' => $antrean->nomor_antrean,
        'status' => 'menunggu',
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);

    expect(Antrean::count())->toBe(1);
});

test('status antrean mengikuti alur menunggu dipanggil dilayani selesai', function () {
    $this->travelTo('2026-10-24 08:00:00');
    $layanan = app(AntreanService::class);
    $antrean = $layanan->ambilNomor(buatPoliAntrean(), buatKunjunganAntrean());

    $antrean = $layanan->panggil($antrean);
    expect($antrean->status)->toBe('dipanggil')
        ->and($antrean->waktu_panggil?->toDateTimeString())->toBe('2026-10-24 08:00:00');

    $antrean = $layanan->layani($antrean);
    expect($antrean->status)->toBe('dilayani');

    $antrean = $layanan->selesai($antrean);
    expect($antrean->status)->toBe('selesai')
        ->and($antrean->waktu_selesai?->toDateTimeString())->toBe('2026-10-24 08:00:00');
});

test('transisi status antrean yang tidak sah ditolak', function (string $aksi, string $statusAwal) {
    $antrean = buatAntreanBerstatus($statusAwal);
    $layanan = app(AntreanService::class);

    expect(fn () => $layanan->{$aksi}($antrean))
        ->toThrow(StatusAntreanTidakValidException::class);

    expect($antrean->fresh()->status)->toBe($statusAwal);
})->with([
    'panggil dari dipanggil' => ['panggil', 'dipanggil'],
    'layani dari menunggu' => ['layani', 'menunggu'],
    'selesai dari menunggu' => ['selesai', 'menunggu'],
    'selesai dari dipanggil' => ['selesai', 'dipanggil'],
    'panggil dari selesai' => ['panggil', 'selesai'],
    'layani dari selesai' => ['layani', 'selesai'],
    'selesai dari selesai' => ['selesai', 'selesai'],
    'batal dari selesai' => ['batal', 'selesai'],
]);

test('antrean dapat dibatalkan dari status aktif dan tidak dapat diubah lagi', function (string $statusAwal) {
    $antrean = buatAntreanBerstatus($statusAwal);
    $layanan = app(AntreanService::class);

    $hasil = $layanan->batal($antrean);

    expect($hasil->status)->toBe('batal')
        ->and($hasil->waktu_selesai)->not->toBeNull();

    expect(fn () => $layanan->panggil($hasil))
        ->toThrow(StatusAntreanTidakValidException::class);
})->with(['menunggu', 'dipanggil', 'dilayani']);
