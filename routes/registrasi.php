<?php

use App\Http\Controllers\Master\AntreanController;
use App\Http\Controllers\Master\KunjunganController;
use App\Http\Controllers\Master\LabAntreanController;
use App\Http\Controllers\Master\LabHasilController;
use App\Http\Controllers\Master\LabMandiriController;
use App\Http\Controllers\Master\PasienController;
use App\Http\Controllers\Master\PemeriksaanController;
use App\Http\Controllers\Master\ResepController;
use App\Http\Controllers\Master\RiwayatKunjunganController;
use App\Http\Controllers\Master\RujukanLabController;
use App\Http\Controllers\Master\SpesimenController;
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

    Route::get('pasien/{pasien}/riwayat', [RiwayatKunjunganController::class, 'index'])->name('riwayat.index');
    Route::get('pasien/{pasien}/riwayat/{kunjungan}', [RiwayatKunjunganController::class, 'show'])->name('riwayat.show');

    Route::get('rujukan-lab', [RujukanLabController::class, 'index'])->name('rujukan-lab.index');
    Route::get('rujukan-lab/create', [RujukanLabController::class, 'create'])->name('rujukan-lab.create');
    Route::post('rujukan-lab', [RujukanLabController::class, 'store'])->name('rujukan-lab.store');
    Route::get('rujukan-lab/{rujukanLab}', [RujukanLabController::class, 'show'])->name('rujukan-lab.show');

    Route::get('lab-mandiri', [LabMandiriController::class, 'index'])->name('lab-mandiri.index');
    Route::get('lab-mandiri/create', [LabMandiriController::class, 'create'])->name('lab-mandiri.create');
    Route::post('lab-mandiri', [LabMandiriController::class, 'store'])->name('lab-mandiri.store');
    Route::get('lab-mandiri/dokter', [LabMandiriController::class, 'dokter'])->name('lab-mandiri.dokter');

    Route::get('lab-antrean', [LabAntreanController::class, 'index'])->name('lab-antrean.index');
    Route::post('lab-antrean/{permintaan}/mulai', [LabAntreanController::class, 'mulai'])->name('lab-antrean.mulai');
    Route::post('lab-antrean/{permintaan}/dokter', [LabAntreanController::class, 'ubahDokter'])->name('lab-antrean.ubah-dokter');

    Route::get('spesimen', [SpesimenController::class, 'index'])->name('spesimen.index');
    Route::get('spesimen/create', [SpesimenController::class, 'create'])->name('spesimen.create');
    Route::post('spesimen', [SpesimenController::class, 'store'])->name('spesimen.store');
    Route::get('spesimen/{spesimen}/edit', [SpesimenController::class, 'edit'])->name('spesimen.edit');
    Route::match(['post', 'patch'], 'spesimen/{spesimen}', [SpesimenController::class, 'update'])->name('spesimen.update');

    Route::get('lab-hasil', [LabHasilController::class, 'index'])->name('lab-hasil.index');
    Route::get('lab-hasil/{permintaan}/edit', [LabHasilController::class, 'edit'])->name('lab-hasil.edit');
    Route::match(['post', 'patch'], 'lab-hasil/{permintaan}', [LabHasilController::class, 'update'])->name('lab-hasil.update');
    Route::post('lab-hasil/{permintaan}/kunci', [LabHasilController::class, 'kunci'])->name('lab-hasil.kunci');
});
