<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WeatherController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Weather API Routes
Route::get('/farms', [WeatherController::class, 'getFarms']);
Route::get('/weather', [WeatherController::class, 'getWeather']);
Route::get('/weather/filtered', [WeatherController::class, 'getFilteredWeather']);
