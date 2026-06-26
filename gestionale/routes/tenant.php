<?php

declare(strict_types=1);

use App\Http\Controllers\Tenant\RepairController;
use App\Http\Controllers\Tenant\QuoteController;
use App\Http\Controllers\Tenant\CustomerController;
use App\Http\Controllers\Tenant\PrintOrderController;
use App\Http\Controllers\Tenant\SettingsController;
use App\Http\Controllers\Tenant\AccountingController;
use App\Http\Controllers\Tenant\PosController;
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

    // POS
    Route::get('/pos',                              [PosController::class, 'index'])->name('pos.index');
    Route::post('/pos/checkout',                    [PosController::class, 'checkout'])->name('pos.checkout');
    Route::get('/pos/barcode',                      [PosController::class, 'barcodeSearch'])->name('pos.barcode');
    Route::get('/pos/sales',                        [PosController::class, 'sales'])->name('pos.sales');
    Route::get('/pos/sales/{sale}',                 [PosController::class, 'showSale'])->name('pos.sales.show');
    Route::patch('/pos/sales/{sale}/void',          [PosController::class, 'voidSale'])->name('pos.sales.void');
    Route::post('/pos/sumup/checkout',              [PosController::class, 'sumupCheckout'])->name('pos.sumup.checkout');
    Route::get('/pos/sumup/status/{checkoutId}',    [PosController::class, 'sumupStatus'])->name('pos.sumup.status');
    Route::post('/pos/woo-sync',                    [PosController::class, 'wooSync'])->name('pos.woo-sync');

    // Contabilità
    Route::get('/accounting',                                [AccountingController::class, 'index'])->name('accounting.index');
    Route::get('/accounting/expenses',                       [AccountingController::class, 'expenses'])->name('accounting.expenses');
    Route::post('/accounting/expenses',                      [AccountingController::class, 'storeExpense'])->name('accounting.expenses.store');
    Route::patch('/accounting/expenses/{expense}',           [AccountingController::class, 'updateExpense'])->name('accounting.expenses.update');
    Route::delete('/accounting/expenses/{expense}',          [AccountingController::class, 'destroyExpense'])->name('accounting.expenses.destroy');
    Route::get('/accounting/purchases',                      [AccountingController::class, 'purchases'])->name('accounting.purchases');
    Route::post('/accounting/purchases',                     [AccountingController::class, 'storePurchase'])->name('accounting.purchases.store');
    Route::get('/accounting/purchases/{purchase}',           [AccountingController::class, 'showPurchase'])->name('accounting.purchases.show');
    Route::patch('/accounting/purchases/{purchase}/status',  [AccountingController::class, 'updatePurchaseStatus'])->name('accounting.purchases.status');
    Route::patch('/accounting/purchases/{purchase}/items/{item}', [AccountingController::class, 'updatePurchaseItem'])->name('accounting.purchases.item');
    Route::get('/accounting/cash',                           [AccountingController::class, 'cashMovements'])->name('accounting.cash');
    Route::post('/accounting/cash',                          [AccountingController::class, 'storeCashMovement'])->name('accounting.cash.store');
    Route::delete('/accounting/cash/{movement}',             [AccountingController::class, 'destroyCashMovement'])->name('accounting.cash.destroy');
    Route::post('/accounting/categories',                    [AccountingController::class, 'storeCategory'])->name('accounting.categories.store');
    Route::delete('/accounting/categories/{category}',       [AccountingController::class, 'destroyCategory'])->name('accounting.categories.destroy');

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
