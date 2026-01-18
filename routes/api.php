<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', action: [AuthController::class, 'logout']);
    Route::apiResource('products', ProductController::class);
    Route::post('/inventory/adjust', [InventoryController::class, 'adjust']);
});
