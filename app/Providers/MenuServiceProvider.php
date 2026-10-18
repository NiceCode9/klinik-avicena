<?php

namespace App\Providers;

use App\Services\IzinService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class MenuServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // @izin('pendaftaran.pasien', 'tambah') ... @endizin
        Blade::if('izin', fn (string $kode, string $aksi = 'lihat') => IzinService::boleh(auth()->user(), $kode, $aksi));

        // Sidebar: $pohonMenu otomatis tersedia di resources/views/layouts/sidebar.blade.php
        View::composer('layouts.sidebar', fn ($view) => $view->with('pohonMenu', IzinService::pohonMenu(auth()->user())));
    }
}
