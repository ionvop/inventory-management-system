<?php

use App\Http\Controllers\ItemController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

Route::get('/', 'App\Http\Controllers\ProfileController@index')->name('profiles.index');
Route::post('/profiles/select/{id}', 'App\Http\Controllers\ProfileController@select')->name('profiles.select');
Route::post('/profiles', 'App\Http\Controllers\ProfileController@store')->name('profiles.store');
Route::patch('/profiles/{id}', 'App\Http\Controllers\ProfileController@update')->name('profiles.update');
Route::delete('/profiles/{id}', 'App\Http\Controllers\ProfileController@destroy')->name('profiles.destroy');
Route::post('/logout', 'App\Http\Controllers\ProfileController@logout')->name('profiles.logout');

Route::middleware('active-profile')->group(function () {
    Route::inertia('/dashboard', 'Dashboard')->name('dashboard');

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
    });
});
