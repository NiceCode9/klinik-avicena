<?php

use App\Http\Controllers\Sistem\HakAksesController;
use Illuminate\Support\Facades\Route;

// Di routes/web.php tambahkan:  require __DIR__ . '/sistem.php';
Route::middleware('auth')->prefix('sistem')->group(function () {
    Route::get('hak-akses', [HakAksesController::class, 'index'])->name('hak-akses.index');
    Route::post('hak-akses/{peran}', [HakAksesController::class, 'simpan'])->name('hak-akses.simpan');
});
