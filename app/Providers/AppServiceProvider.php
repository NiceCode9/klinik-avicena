<?php

namespace App\Providers;

use App\Models\Master\Dokter;
use App\Models\Master\Icd10;
use App\Models\Master\KategoriObat;
use App\Models\Master\MetodeBayar;
use App\Models\Master\Obat;
use App\Models\Master\ParameterLab;
use App\Models\Master\PemeriksaanLab;
use App\Models\Master\Poli;
use App\Models\Master\Racikan;
use App\Models\Master\RacikanBahan;
use App\Models\Master\Satuan;
use App\Models\Master\Supplier;
use App\Models\Master\Tindakan;
use App\Models\Sys\Peran;
use App\Models\User;
use App\Observers\CatatAktivitasObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Batas percobaan login: per akun+IP, plus batas per IP agar banyak akun
        // tidak bisa dicoba beruntun dari satu mesin (LAN klinik ber-IP sama).
        RateLimiter::for('login', function (Request $request) {
            $pesan = fn () => back()
                ->withErrors(['username' => __('auth.throttle', ['seconds' => 60])])
                ->onlyInput('username');

            $username = $request->input('username');
            $perAkun = Str::transliterate(Str::lower(is_string($username) ? $username : '').'|'.$request->ip());

            return [
                Limit::perMinute(5)->by($perAkun)->response($pesan),
                Limit::perMinute(20)->by((string) $request->ip())->response($pesan),
            ];
        });

        // Paginator bergaya Bootstrap 5 agar cocok dengan AdminLTE.
        Paginator::useBootstrapFive();

        // Tabel transaksional (lab, billing, stok) sengaja tidak dipantau agar log tidak banjir.
        $modelTercatat = [
            Poli::class, Dokter::class, MetodeBayar::class, Satuan::class, KategoriObat::class,
            Obat::class, Supplier::class, Tindakan::class, PemeriksaanLab::class, ParameterLab::class,
            Racikan::class, RacikanBahan::class, Icd10::class, User::class, Peran::class,
        ];

        foreach ($modelTercatat as $model) {
            $model::observe(CatatAktivitasObserver::class);
        }
    }
}
