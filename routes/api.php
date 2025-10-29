<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\GameGroupController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\GameCategoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('login', [AdminAuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AdminAuthController::class, 'logout']);
    Route::get('mine', [AdminAuthController::class, 'mine']);
    
    // Themes routes
    Route::get('themes', [ThemeController::class, 'index']);
    Route::post('themes', [ThemeController::class, 'store']);
    Route::get('themes/{theme}', [ThemeController::class, 'show']);
    Route::put('themes/{theme}', [ThemeController::class, 'update']);
    
    // Game categories routes
    Route::get('game-categories', [GameCategoryController::class, 'index']);
    Route::post('game-categories', [GameCategoryController::class, 'store']);
    Route::get('game-categories/{gameCategory}', [GameCategoryController::class, 'show']);
    Route::put('game-categories/{gameCategory}', [GameCategoryController::class, 'update']);
    
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
           
           // Game groups routes
           Route::get('game-groups', [GameGroupController::class, 'index']);
           Route::post('game-groups', [GameGroupController::class, 'store']);
           Route::get('game-groups/{gameGroup}', [GameGroupController::class, 'show']);
           Route::put('game-groups/{gameGroup}', [GameGroupController::class, 'update']);
           Route::delete('game-groups/{gameGroup}', [GameGroupController::class, 'destroy']);
           
           // Game group games routes
           Route::post('game-groups/{gameGroup}/games', [GameGroupController::class, 'attachGame']);
           Route::post('game-groups/{gameGroup}/games/attach', [GameGroupController::class, 'attachGames']);
           Route::delete('game-groups/{gameGroup}/games/{game}', [GameGroupController::class, 'detachGame']);
           Route::post('game-groups/{gameGroup}/games/detach', [GameGroupController::class, 'detachGames']);
           Route::put('game-groups/{gameGroup}/games/order', [GameGroupController::class, 'updateGameOrder']);
           
           // Orders routes
           Route::get('orders', [OrderController::class, 'index']);
           
           // Payment methods routes
           Route::get('payment-methods', [PaymentMethodController::class, 'index']);
           Route::put('payment-methods/{paymentMethod}', [PaymentMethodController::class, 'update']);
       });