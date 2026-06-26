<?php

declare(strict_types=1);

use App\Http\Controllers\Tenant\RepairController;
use Illuminate\Support\Facades\Route;

// ─── Pubblico: solo chi ha il token ───────────────────────────────────────────
Route::get('/track/{token}', [RepairController::class, 'track'])->name('track.repair');

// ─── Auth ─────────────────────────────────────────────────────────────────────
Route::get('/login', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'create'])->name('login');
Route::post('/login', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'store']);
Route::post('/logout', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'destroy'])->name('logout');

// ─── Protetto da autenticazione ───────────────────────────────────────────────
Route::middleware(['auth'])->group(function () {

    Route::get('/', fn() => redirect()->route('repairs.index'));

    // Riparazioni
    Route::resource('repairs', RepairController::class);
    Route::patch('/repairs/{repair}/status', [RepairController::class, 'updateStatus'])->name('repairs.status');

});
