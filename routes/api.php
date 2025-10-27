<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\BannerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('login', [AdminAuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AdminAuthController::class, 'logout']);
    Route::get('mine', [AdminAuthController::class, 'mine']);
    
    // Tags routes
    Route::get('tags', [TagController::class, 'index']);
    Route::post('tags', [TagController::class, 'store']);
    Route::get('tags/{tag}', [TagController::class, 'show']);
    Route::put('tags/{tag}', [TagController::class, 'update']);
    
    // Brands routes
    Route::get('brands', [BrandController::class, 'index']);
    Route::get('brands/options', [BrandController::class, 'options']);
    Route::get('brands/{brand}', [BrandController::class, 'show']);
    Route::post('brands', [BrandController::class, 'store']);
    Route::put('brands/{brand}', [BrandController::class, 'update']);
    Route::delete('brands/{brand}', [BrandController::class, 'destroy']);
    
           // Brand details routes
           Route::post('brands/{brand}/details', [BrandController::class, 'storeDetail']);
           Route::put('brands/{brand}/details/{detail}', [BrandController::class, 'updateDetail']);
           Route::delete('brands/{brand}/details/{detail}', [BrandController::class, 'destroyDetail']);

           // Upload routes
           Route::post('upload', [UploadController::class, 'file']);

           // Games routes
           Route::get('games', [GameController::class, 'index']);
           Route::get('games/{game}', [GameController::class, 'show']);
           Route::put('games/{game}', [GameController::class, 'update']);
           
           // Banners routes
           Route::get('banners', [BannerController::class, 'index']);
           Route::post('banners', [BannerController::class, 'store']);
           Route::get('banners/{banner}', [BannerController::class, 'show']);
           Route::put('banners/{banner}', [BannerController::class, 'update']);
           Route::delete('banners/{banner}', [BannerController::class, 'destroy']);
       });