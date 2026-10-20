<?php

use App\Http\Controllers\Sistem\HakAksesController;
use App\Http\Controllers\Sistem\PengaturanController;
use App\Http\Controllers\Sistem\PenggunaController;
use App\Http\Controllers\Sistem\PeranController;
use Illuminate\Support\Facades\Route;

// Di routes/web.php tambahkan:  require __DIR__ . '/sistem.php';
Route::middleware('auth')->prefix('sistem')->group(function () {
    Route::resource('pengguna', PenggunaController::class)->except('show');
    Route::resource('peran', PeranController::class)->except('show');
    Route::get('hak-akses', [HakAksesController::class, 'index'])->name('hak-akses.index');
    Route::post('hak-akses/{peran}', [HakAksesController::class, 'simpan'])->name('hak-akses.simpan');
    Route::get('pengaturan', [PengaturanController::class, 'index'])->name('pengaturan.index');
    Route::put('pengaturan', [PengaturanController::class, 'update'])->name('pengaturan.update');
});
