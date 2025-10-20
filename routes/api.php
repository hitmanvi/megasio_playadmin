<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\BrandController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('login', [AdminAuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AdminAuthController::class, 'logout']);
    Route::get('mine', [AdminAuthController::class, 'mine']);
    
    // Tags routes
    Route::get('tags', [TagController::class, 'index']);
    Route::get('tags/{tag}', [TagController::class, 'show']);
    Route::put('tags/{tag}', [TagController::class, 'update']);
    
    // Brands routes
    Route::get('brands', [BrandController::class, 'index']);
    Route::get('brands/{brand}', [BrandController::class, 'show']);
    Route::post('brands', [BrandController::class, 'store']);
    Route::put('brands/{brand}', [BrandController::class, 'update']);
    Route::delete('brands/{brand}', [BrandController::class, 'destroy']);
    
    // Brand details routes
    Route::post('brands/{brand}/details', [BrandController::class, 'storeDetail']);
    Route::put('brands/{brand}/details/{detail}', [BrandController::class, 'updateDetail']);
    Route::delete('brands/{brand}/details/{detail}', [BrandController::class, 'destroyDetail']);
});