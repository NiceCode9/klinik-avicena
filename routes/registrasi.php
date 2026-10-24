<?php

use App\Http\Controllers\Master\PasienController;
use Illuminate\Support\Facades\Route;

// Di routes/web.php tambahkan: require __DIR__ . '/registrasi.php';
Route::middleware('auth')->prefix('pendaftaran')->group(function () {
    Route::resource('pasien', PasienController::class)->except('destroy')->parameters(['pasien' => 'pasien']);
});
