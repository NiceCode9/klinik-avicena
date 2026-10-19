<?php

use App\Http\Controllers\Auth\KataSandiController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->controller(LoginController::class)->group(function () {
    Route::get('login', 'form')->name('login');
    Route::post('login', 'login')->name('login.attempt')->middleware('throttle:login');
});

Route::post('logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard')->middleware('izin:dasbor,lihat');

    Route::get('ganti-kata-sandi', [KataSandiController::class, 'edit'])->name('kata-sandi.edit');
    Route::put('ganti-kata-sandi', [KataSandiController::class, 'update'])->name('kata-sandi.update');
});

require __DIR__.'/sistem.php';
require __DIR__.'/master.php';
