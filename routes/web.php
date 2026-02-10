<?php

use Illuminate\Support\Facades\Route;

// Frontend Route (SPA) - Must be last
Route::get('/{path?}', function () {
    return view('app');
})->where('path', '^(?!api).*');
