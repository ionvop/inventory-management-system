<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\PeriodController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierItemController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/', 'App\Http\Controllers\ProfileController@index')->name('profiles.index');
Route::post('/profiles/select/{id}', 'App\Http\Controllers\ProfileController@select')->name('profiles.select');
Route::post('/profiles', 'App\Http\Controllers\ProfileController@store')->name('profiles.store');
Route::patch('/profiles/{id}', 'App\Http\Controllers\ProfileController@update')->name('profiles.update');
Route::delete('/profiles/{id}', 'App\Http\Controllers\ProfileController@destroy')->name('profiles.destroy');
Route::post('/logout', 'App\Http\Controllers\ProfileController@logout')->name('profiles.logout');

Route::middleware('active-profile')->group(function () {
    Route::inertia('/dashboard', 'Dashboard')->name('dashboard');

    // Stock movements are recorded by any role (FR-4.1). Transactions are
    // immutable once saved, so there are no update or delete routes (FR-4.4).
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');

    // Corrections are made by posting a reversing transaction that references
    // the original (FR-4.4). This is a high-impact action, so it is restricted
    // to administrators (NFR-2.2).
    Route::middleware('role:administrator')->group(function () {
        Route::post('/transactions/{id}/reverse', [TransactionController::class, 'reverse'])->name('transactions.reverse');
    });

    // The near-expiry / expired batch dashboard is available to every role,
    // since staff perform the pull-outs (FR-3.3).
    Route::get('/batches', [BatchController::class, 'index'])->name('batches.index');

    // Period closing is a supervisor/administrator action (FR-6.2); reopening
    // a closed period is restricted to administrators (FR-6.4).
    Route::middleware('role:supervisor,administrator')->group(function () {
        Route::get('/periods', [PeriodController::class, 'index'])->name('periods.index');
        Route::post('/periods/{id}/close', [PeriodController::class, 'close'])->name('periods.close');

        // The monthly report is a supervisor/administrator activity, matching
        // period closing, since it carries the department's sign-off (FR-7.1).
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

        // The report is exportable to Excel (FR-7.3).
        Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
    });

    Route::middleware('role:administrator')->group(function () {
        Route::post('/periods/{id}/reopen', [PeriodController::class, 'reopen'])->name('periods.reopen');

        // The audit trail is reviewable by administrators only (FR-8.2).
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    });

    // Catalog management is restricted to administrators (FR-2.1, FR-2.2).
    Route::middleware('role:administrator')->group(function () {
        Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
        Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
        Route::patch('/suppliers/{id}', [SupplierController::class, 'update'])->name('suppliers.update');
        Route::delete('/suppliers/{id}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');

        Route::get('/items', [ItemController::class, 'index'])->name('items.index');
        Route::post('/items', [ItemController::class, 'store'])->name('items.store');
        Route::patch('/items/{id}', [ItemController::class, 'update'])->name('items.update');
        Route::delete('/items/{id}', [ItemController::class, 'destroy'])->name('items.destroy');

        // Contract pricing links a supplier and an item at a dated price (FR-2.3).
        Route::get('/supplier-items', [SupplierItemController::class, 'index'])->name('supplier-items.index');
        Route::post('/supplier-items', [SupplierItemController::class, 'store'])->name('supplier-items.store');
        Route::patch('/supplier-items/{id}', [SupplierItemController::class, 'update'])->name('supplier-items.update');
        Route::delete('/supplier-items/{id}', [SupplierItemController::class, 'destroy'])->name('supplier-items.destroy');
    });
});
