<?php

use App\Http\Controllers\Auth\CentralAuthController;
use Illuminate\Support\Facades\Route;

// ─── Auth (centrale, nessun tenant richiesto) ─────────────────────────────────
Route::get('/login',  [CentralAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [CentralAuthController::class, 'login'])->name('login.submit');
Route::post('/logout',[CentralAuthController::class, 'logout'])->name('logout');

// ─── Selezione attività (richiede login, non richiede tenant) ─────────────────
Route::middleware(['auth'])->group(function () {
    Route::get('/',               [CentralAuthController::class, 'selectTenant'])->name('tenant.select');
    Route::post('/select-tenant', [CentralAuthController::class, 'switchTenant'])->name('tenant.switch');
});

// ─── Rotte tenant (richiedono login + tenant in sessione) ─────────────────────
Route::middleware(['auth', \App\Http\Middleware\InitializeTenancyBySession::class])
    ->group(base_path('routes/tenant.php'));
