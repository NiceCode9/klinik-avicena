<?php

namespace App\Providers;

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
    }
}
