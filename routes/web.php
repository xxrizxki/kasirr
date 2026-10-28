<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KasirController;
use App\Http\Controllers\PesananController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\HargaController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SizeController;
use App\Http\Controllers\AuthController;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login.form')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->name('login')->middleware(['guest', 'throttle:5,1']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/', [KasirController::class, 'index'])->name('kasir');
    Route::post('/pesanan', [PesananController::class, 'store'])->name('pesanan.store');
    Route::get('/pesanan', [PesananController::class, 'index'])->name('pesanan.index');
    Route::get('/pesanan/{order}', [PesananController::class, 'show'])->name('pesanan.show');
    Route::post('/pesanan/{order}/batal', [PesananController::class, 'batal'])->name('pesanan.batal');
    Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('harga', [HargaController::class, 'edit'])->name('harga.edit');
    Route::put('harga', [HargaController::class, 'update'])->name('harga.update');
    Route::post('harga/massal', [HargaController::class, 'massal'])->name('harga.massal');
    Route::resource('kategori', CategoryController::class)->except('show')->parameters(['kategori' => 'category']);
    Route::resource('ukuran', SizeController::class)->except('show')->parameters(['ukuran' => 'size']);
    Route::get('ukuran/{size}/riwayat-harga', [SizeController::class, 'riwayat'])->name('ukuran.riwayat');
});
