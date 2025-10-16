<?php

use App\Http\Controllers\AdminAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Admin authentication routes
Route::prefix('admin')->group(function () {
    // Public routes (no authentication required)
    Route::post('login', [AdminAuthController::class, 'login']);
    
    // Protected routes (authentication required)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AdminAuthController::class, 'logout']);
        Route::get('mine', [AdminAuthController::class, 'mine']);
    });
});
