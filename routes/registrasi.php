<?php

use App\Http\Controllers\Master\AntreanController;
use App\Http\Controllers\Master\KunjunganController;
use App\Http\Controllers\Master\PasienController;
use App\Http\Controllers\Master\PemeriksaanController;
use App\Http\Controllers\Master\ResepController;
use App\Http\Controllers\Master\RujukanLabController;
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

    Route::get('antrean', [AntreanController::class, 'index'])->name('antrean.index');
    Route::get('antrean/layar', [AntreanController::class, 'layar'])->name('antrean.layar');
    Route::get('antrean/json', [AntreanController::class, 'json'])->name('antrean.json');
    Route::post('antrean/{antrean}/panggil', [AntreanController::class, 'panggil'])->name('antrean.panggil');
    Route::post('antrean/{antrean}/layani', [AntreanController::class, 'layani'])->name('antrean.layani');
    Route::post('antrean/{antrean}/selesai', [AntreanController::class, 'selesai'])->name('antrean.selesai');
    Route::post('antrean/{antrean}/batal', [AntreanController::class, 'batal'])->name('antrean.batal');

    Route::get('pemeriksaan', [PemeriksaanController::class, 'index'])->name('pemeriksaan.index');
    Route::get('pemeriksaan/icd10', [PemeriksaanController::class, 'icd10'])->name('pemeriksaan.icd10');
    Route::post('kunjungan/{kunjungan}/pemeriksaan/mulai', [PemeriksaanController::class, 'mulai'])->name('pemeriksaan.mulai');
    Route::get('pemeriksaan/{pemeriksaan}/edit', [PemeriksaanController::class, 'edit'])->name('pemeriksaan.edit');
    Route::put('pemeriksaan/{pemeriksaan}', [PemeriksaanController::class, 'update'])->name('pemeriksaan.update');
    Route::post('pemeriksaan/{pemeriksaan}/detail-pd', [PemeriksaanController::class, 'simpanDetailPd'])->name('pemeriksaan.detail-pd');
    Route::post('pemeriksaan/{pemeriksaan}/detail-saraf', [PemeriksaanController::class, 'simpanDetailSaraf'])->name('pemeriksaan.detail-saraf');
    Route::post('pemeriksaan/{pemeriksaan}/odontogram', [PemeriksaanController::class, 'simpanOdontogram'])->name('pemeriksaan.odontogram');
    Route::post('pemeriksaan/{pemeriksaan}/tindakan', [PemeriksaanController::class, 'simpanTindakan'])->name('pemeriksaan.tindakan');

    Route::get('resep', [ResepController::class, 'index'])->name('resep.index');
    Route::get('resep/create', [ResepController::class, 'create'])->name('resep.create');
    Route::post('resep', [ResepController::class, 'store'])->name('resep.store');
    Route::get('resep/{resep}', [ResepController::class, 'show'])->name('resep.show');

    Route::get('rujukan-lab', [RujukanLabController::class, 'index'])->name('rujukan-lab.index');
    Route::get('rujukan-lab/create', [RujukanLabController::class, 'create'])->name('rujukan-lab.create');
    Route::post('rujukan-lab', [RujukanLabController::class, 'store'])->name('rujukan-lab.store');
    Route::get('rujukan-lab/{rujukanLab}', [RujukanLabController::class, 'show'])->name('rujukan-lab.show');
});
