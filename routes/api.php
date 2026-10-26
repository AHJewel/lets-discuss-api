<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\RegisterController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

// Stateless on purpose: the session store is Redis, and health must still answer when Redis is down
Route::get('/health', HealthController::class)
    ->withoutMiddleware(EnsureFrontendRequestsAreStateful::class)
    ->name('health');

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', RegisterController::class)->middleware('throttle:register')->name('register');
