<?php

use App\Http\Controllers\Master\DokterController;
use App\Http\Controllers\Master\KategoriObatController;
use App\Http\Controllers\Master\MetodeBayarController;
use App\Http\Controllers\Master\ObatController;
use App\Http\Controllers\Master\PemeriksaanLabController;
use App\Http\Controllers\Master\PoliController;
use App\Http\Controllers\Master\RacikanController;
use App\Http\Controllers\Master\SatuanController;
use App\Http\Controllers\Master\SupplierController;
use App\Http\Controllers\Master\TindakanController;
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

    Route::resource('supplier', SupplierController::class)->except('show');
    Route::resource('tindakan', TindakanController::class)->except('show');
    Route::resource('obat', ObatController::class)->except('show');
    Route::resource('racikan', RacikanController::class)->except('show');
    Route::resource('pemeriksaan-lab', PemeriksaanLabController::class)->except('show')
        ->parameters(['pemeriksaan-lab' => 'pemeriksaanLab']);
});
