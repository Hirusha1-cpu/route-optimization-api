<?php

use Illuminate\Support\Facades\Route;

// 👇 SPA catch-all route
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');