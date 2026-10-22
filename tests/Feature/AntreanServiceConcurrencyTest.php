<?php

use App\Models\Master\Pasien;
use App\Models\Master\Poli;
use App\Models\Registrasi\Antrean;
use App\Models\Registrasi\Kunjungan;
use App\Models\Registrasi\PenghitungAntrean;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Process;

uses(DatabaseMigrations::class);

function buatPasienKonkuren(): Pasien
{
    return Pasien::create([
        'no_rekam_medis' => 'RM-KONKUREN',
        'nik' => '3201999999999999',
        'nama_lengkap' => 'Pasien Konkuren',
        'tanggal_lahir' => '1990-01-01',
        'jenis_kelamin' => 'P',
        'golongan_darah' => 'O',
    ]);
}

test('dua proses paralel mendapat nomor antrean berbeda tanpa duplikat', function () {
    $poli = Poli::create(['kode' => 'INT', 'nama' => 'Poli Umum']);
    $kunjungan = Kunjungan::create([
        'no_kunjungan' => 'KJ-KONKUREN',
        'pasien_id' => buatPasienKonkuren()->id,
        'tanggal_kunjungan' => today(),
        'jenis_kunjungan' => 'poli',
        'status' => 'terdaftar',
    ]);

    $kode = <<<'PHP'
$base = $argv[1];
require $base.'/vendor/autoload.php';
$app = require $base.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$poli = \App\Models\Master\Poli::findOrFail((int) $argv[2]);
$kunjungan = \App\Models\Registrasi\Kunjungan::findOrFail((int) $argv[3]);
$mulai = (float) $argv[4];
$sisa = $mulai - microtime(true);

if ($sisa > 0) {
    usleep((int) ($sisa * 1000000));
}

$antrean = app(\App\Services\Registrasi\AntreanService::class)->ambilNomor($poli, $kunjungan);
echo $antrean->nomor_urut;
PHP;

    $perintah = [
        PHP_BINARY,
        '-r',
        $kode,
        '--',
        base_path(),
        (string) $poli->id,
        (string) $kunjungan->id,
        (string) (microtime(true) + 1.5),
    ];

    $env = [
        'APP_ENV' => 'testing',
        'APP_KEY' => (string) config('app.key'),
        'DB_CONNECTION' => (string) config('database.default'),
        'DB_HOST' => (string) config('database.connections.mysql.host'),
        'DB_PORT' => (string) config('database.connections.mysql.port'),
        'DB_DATABASE' => (string) config('database.connections.mysql.database'),
        'DB_USERNAME' => (string) config('database.connections.mysql.username'),
        'DB_PASSWORD' => (string) config('database.connections.mysql.password'),
        'DB_URL' => '',
        'CACHE_STORE' => 'array',
        'SESSION_DRIVER' => 'array',
        'QUEUE_CONNECTION' => 'sync',
    ];

    $proses = [];

    try {
        foreach ([1, 2] as $_) {
            $proses[] = Process::env($env)
                ->path(base_path())
                ->timeout(30)
                ->start($perintah);
        }

        $hasil = array_map(fn ($proses) => $proses->wait(), $proses);
    } finally {
        foreach ($proses as $proses) {
            if ($proses->running()) {
                $proses->stop();
            }
        }
    }

    expect($hasil[0]->exitCode())->toBe(0)
        ->and($hasil[1]->exitCode())->toBe(0);

    $keluaran = collect($hasil)
        ->map(fn ($hasil) => (int) trim($hasil->output()))
        ->sort()
        ->values()
        ->all();

    expect($keluaran)->toBe([1, 2])
        ->and(Antrean::orderBy('nomor_urut')->pluck('nomor_urut')->all())->toBe([1, 2])
        ->and(Antrean::count())->toBe(2)
        ->and(PenghitungAntrean::where('poli_id', $poli->id)
            ->whereDate('tanggal', today())
            ->value('nomor_terakhir'))->toBe(2);
});
