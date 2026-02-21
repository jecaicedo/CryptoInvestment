<?php

use App\Http\Controllers\CryptoController;
use Illuminate\Support\Facades\Route;

Route::get('/crypto/search', [CryptoController::class, 'search']);
Route::get('/crypto/tracked', [CryptoController::class, 'getTracked']);
Route::post('/crypto/track', [CryptoController::class, 'track']);
Route::delete('/crypto/track/{id}', [CryptoController::class, 'untrack']);
Route::get('/crypto/{id}/history', [CryptoController::class, 'history']);
Route::get('/crypto/coingecko-history', [CryptoController::class, 'coinGeckoHistory']);