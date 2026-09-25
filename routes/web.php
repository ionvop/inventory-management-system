<?php

use Illuminate\Support\Facades\Route;

Route::get('/', 'App\Http\Controllers\ProfileController@index')->name('profiles.index');
Route::post('/profiles/select/{id}', 'App\Http\Controllers\ProfileController@select')->name('profiles.select');
Route::post('/profiles', 'App\Http\Controllers\ProfileController@store')->name('profiles.store');
Route::patch('/profiles/{id}', 'App\Http\Controllers\ProfileController@update')->name('profiles.update');
Route::delete('/profiles/{id}', 'App\Http\Controllers\ProfileController@destroy')->name('profiles.destroy');
Route::post('/logout', 'App\Http\Controllers\ProfileController@logout')->name('profiles.logout');

Route::inertia('/dashboard', 'Dashboard')->name('dashboard');
