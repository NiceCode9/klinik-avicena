<?php

use App\Http\Controllers\Master\SatuanController;
use Illuminate\Support\Facades\Route;

// Di routes/web.php tambahkan:  require __DIR__ . '/master.php';
Route::middleware('auth')->prefix('master')->group(function () {
    Route::resource('satuan', SatuanController::class)->except('show');
});
