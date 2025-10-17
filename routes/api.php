<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\TagController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('login', [AdminAuthController::class, 'login']);


// Tags routes
Route::get('tags', [TagController::class, 'index']);
Route::put('tags/{tag}', [TagController::class, 'update']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AdminAuthController::class, 'logout']);
    Route::get('mine', [AdminAuthController::class, 'mine']);
    

});
