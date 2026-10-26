<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KasirController;
use App\Http\Controllers\PesananController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\HargaController;
use App\Http\Controllers\AuthController;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login.form');
Route::post('/login', [AuthController::class, 'login'])->name('login')->middleware('throttle:5,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/', [KasirController::class, 'index'])->name('kasir');
Route::post('/pesanan', [PesananController::class, 'store'])->name('pesanan.store');
Route::get('/pesanan', [PesananController::class, 'index'])->name('pesanan.index');
Route::get('/pesanan/{order}', [PesananController::class, 'show'])->name('pesanan.show');
Route::post('/pesanan/{order}/batal', [PesananController::class, 'batal'])->name('pesanan.batal');

Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan');

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/harga', [HargaController::class, 'edit'])->name('admin.harga.edit');
    Route::put('/admin/harga', [HargaController::class, 'update'])->name('admin.harga.update');
    Route::post('/admin/harga/massal', [HargaController::class, 'massal'])->name('admin.harga.massal');
});
