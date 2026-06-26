<?php

declare(strict_types=1);

use App\Http\Controllers\Tenant\RepairController;
use App\Http\Controllers\Tenant\QuoteController;
use App\Http\Controllers\Tenant\CustomerController;
use App\Http\Controllers\Tenant\PrintOrderController;
use App\Http\Controllers\Tenant\SettingsController;
use Illuminate\Support\Facades\Route;

// ─── Pubblico: solo chi ha il token ───────────────────────────────────────────
Route::get('/track/{token}',           [RepairController::class, 'track'])->name('track.repair');
Route::get('/track-order/{token}',     [PrintOrderController::class, 'trackPublic'])->name('track.print-order');
Route::get('/quote/{token}',           [QuoteController::class, 'respond'])->name('quote.respond');
Route::post('/quote/{token}/respond',  [QuoteController::class, 'submitResponse'])->name('quote.respond.submit');

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

    // Clienti
    Route::resource('customers', CustomerController::class);

    // Ordini stampa DTF
    Route::resource('print-orders', PrintOrderController::class);
    Route::patch('/print-orders/{printOrder}/status',  [PrintOrderController::class, 'updateStatus'])->name('print-orders.status');
    Route::post('/print-orders/{printOrder}/notify',   [PrintOrderController::class, 'notify'])->name('print-orders.notify');

    // Preventivi
    Route::resource('quotes', QuoteController::class)->except(['edit', 'update', 'destroy']);
    Route::post('/quotes/{quote}/send', [QuoteController::class, 'send'])->name('quotes.send');

    // Impostazioni tenant
    Route::get('/settings',              [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings/branding',    [SettingsController::class, 'updateBranding'])->name('settings.branding');
    Route::post('/settings/business',    [SettingsController::class, 'updateBusiness'])->name('settings.business');
    Route::post('/settings/invoice',     [SettingsController::class, 'updateInvoiceLayout'])->name('settings.invoice');
    Route::post('/settings/smtp',        [SettingsController::class, 'updateSmtp'])->name('settings.smtp');
    Route::post('/settings/smtp/test',   [SettingsController::class, 'testSmtp'])->name('settings.smtp.test');
    Route::post('/settings/bulkgate',    [SettingsController::class, 'updateBulkgate'])->name('settings.bulkgate');
    Route::post('/settings/bulkgate/test', [SettingsController::class, 'testBulkgate'])->name('settings.bulkgate.test');
    Route::post('/settings/sumup',       [SettingsController::class, 'updateSumup'])->name('settings.sumup');
    Route::post('/settings/modules',     [SettingsController::class, 'updateModules'])->name('settings.modules');

});
