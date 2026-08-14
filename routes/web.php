<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// SPA catch-all: hand every other non-API route to the React app.
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '^(?!api/|up$).*');
