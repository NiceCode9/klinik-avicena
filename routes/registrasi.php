<?php

use App\Http\Controllers\Master\KunjunganController;
use App\Http\Controllers\Master\PasienController;
use App\Http\Controllers\Master\TtvController;
use Illuminate\Support\Facades\Route;

// Di routes/web.php tambahkan: require __DIR__ . '/registrasi.php';
Route::middleware('auth')->prefix('pendaftaran')->group(function () {
    Route::resource('pasien', PasienController::class)->except('destroy')->parameters(['pasien' => 'pasien']);

    Route::resource('kunjungan', KunjunganController::class)->except('edit', 'update', 'destroy')
        ->parameters(['kunjungan' => 'kunjungan']);
    Route::get('kunjungan/{kunjungan}/cetak', [KunjunganController::class, 'cetak'])
        ->name('kunjungan.cetak');

    Route::get('ttv', [TtvController::class, 'index'])->name('ttv.index');
    Route::get('kunjungan/{kunjungan}/ttv/create', [TtvController::class, 'create'])->name('ttv.create');
    Route::post('kunjungan/{kunjungan}/ttv', [TtvController::class, 'store'])->name('ttv.store');
});
