<?php

use App\Http\Controllers\Master\DokterController;
use App\Http\Controllers\Master\KategoriObatController;
use App\Http\Controllers\Master\MetodeBayarController;
use App\Http\Controllers\Master\PoliController;
use App\Http\Controllers\Master\SatuanController;
use Illuminate\Support\Facades\Route;

// Di routes/web.php tambahkan:  require __DIR__ . '/master.php';
Route::middleware('auth')->prefix('master')->group(function () {
    Route::resource('satuan', SatuanController::class)->except('show');

    Route::resource('poli', PoliController::class)->except('show');
    Route::resource('dokter', DokterController::class)->except('show');
    Route::resource('metode-bayar', MetodeBayarController::class)->except('show')
        ->parameters(['metode-bayar' => 'metodeBayar']);
    Route::resource('kategori-obat', KategoriObatController::class)->except('show')
        ->parameters(['kategori-obat' => 'kategoriObat']);
});
