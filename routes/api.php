<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\GameGroupController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderArchiveController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\GameCategoryController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\WithdrawController;
use App\Http\Controllers\UserActivityController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\KycController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\BundleController;
use App\Http\Controllers\BundlePurchaseController;
use App\Http\Controllers\RedeemController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\BlacklistController;
use App\Http\Controllers\UserTagLogController;
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
           Route::get('order-archives', [OrderArchiveController::class, 'index']);
           
           // Payment methods routes
           Route::get('payment-methods', [PaymentMethodController::class, 'index']);
           Route::post('payment-methods', [PaymentMethodController::class, 'store']);
           Route::get('payment-methods/{paymentMethod}', [PaymentMethodController::class, 'show']);
           Route::put('payment-methods/{paymentMethod}', [PaymentMethodController::class, 'update']);
           Route::post('payment-methods/sync', [PaymentMethodController::class, 'sync']);
           
           // Deposits routes
           Route::get('deposits', [DepositController::class, 'index']);
           Route::post('deposits/{deposit}/resolve', [DepositController::class, 'resolve']);
           
           // Withdraws routes
           Route::get('withdraws', [WithdrawController::class, 'index']);
           Route::get('withdraws/counts', [WithdrawController::class, 'counts']);
           Route::post('withdraws/{withdraw}/pass', [WithdrawController::class, 'pass']);
           Route::post('withdraws/{withdraw}/reject', [WithdrawController::class, 'reject']);
           
           // User activities routes
           Route::get('user-activities', [UserActivityController::class, 'index']);
           
           // Users routes
           Route::get('users', [UserController::class, 'index']);
           Route::post('users/{user}/ban', [UserController::class, 'ban']);
           Route::post('users/{user}/unban', [UserController::class, 'unban']);
           
           // Currencies routes
           Route::get('currencies', [CurrencyController::class, 'index']);
           Route::post('currencies', [CurrencyController::class, 'store']);
           Route::get('currencies/{currency}', [CurrencyController::class, 'show']);
           Route::put('currencies/{currency}', [CurrencyController::class, 'update']);
           Route::delete('currencies/{currency}', [CurrencyController::class, 'destroy']);
           
           // KYCs routes
           Route::get('kycs', [KycController::class, 'index']);
           Route::post('kycs/{kyc}/approve', [KycController::class, 'approve']);
           Route::post('kycs/{kyc}/reject', [KycController::class, 'reject']);
           
           // Settings routes
           Route::get('settings', [SettingController::class, 'index']);
           Route::post('settings', [SettingController::class, 'store']);
           Route::post('settings/batch', [SettingController::class, 'batchUpdate']);
           Route::get('settings/{setting}', [SettingController::class, 'show']);
           Route::put('settings/{setting}', [SettingController::class, 'update']);
           Route::delete('settings/{setting}', [SettingController::class, 'destroy']);
           
           // Bundles routes
           Route::get('bundles', [BundleController::class, 'index']);
           Route::post('bundles', [BundleController::class, 'store']);
           Route::get('bundles/{bundle}', [BundleController::class, 'show']);
           Route::put('bundles/{bundle}', [BundleController::class, 'update']);
           Route::delete('bundles/{bundle}', [BundleController::class, 'destroy']);
           
           // Bundle purchases routes
           Route::get('bundle-purchases', [BundlePurchaseController::class, 'index']);
           
           // Redeems routes
           Route::get('redeems', [RedeemController::class, 'index']);
           Route::post('redeems/{redeem}/pass', [RedeemController::class, 'pass']);
           Route::post('redeems/{redeem}/reject', [RedeemController::class, 'reject']);
           
           // Tags routes
           Route::get('tags', [TagController::class, 'index']);
           Route::post('tags', [TagController::class, 'store']);
           Route::get('tags/{tag}', [TagController::class, 'show']);
           Route::put('tags/{tag}', [TagController::class, 'update']);
           Route::delete('tags/{tag}', [TagController::class, 'destroy']);
           Route::get('tags/{tag}/users', [TagController::class, 'getUsers']);
           
           // User tags routes
           Route::post('users/{user}/tags/attach', [TagController::class, 'attachToUser']);
           Route::post('users/{user}/tags/detach', [TagController::class, 'detachFromUser']);
           Route::post('users/{user}/tags/sync', [TagController::class, 'syncUserTags']);
           
           // Blacklist routes
           Route::get('blacklists', [BlacklistController::class, 'index']);
           Route::post('blacklists', [BlacklistController::class, 'store']);
           Route::delete('blacklists/{blacklist}', [BlacklistController::class, 'destroy']);
           
           // User tag logs routes
           Route::get('user-tag-logs', [UserTagLogController::class, 'index']);
       });