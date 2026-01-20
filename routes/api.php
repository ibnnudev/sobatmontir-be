<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // -- INVENTORY MANAGEMENT --
    Route::apiResource('products', ProductController::class);
    Route::post('/inventory/adjust', [InventoryController::class, 'adjust']);

    // -- SHIFT MANAGEMENT --
    Route::prefix('shifts')->group(function () {
        Route::post('/open', [ShiftController::class, 'open']);
        Route::post('/close', [ShiftController::class, 'close']);
        Route::get('/current', [ShiftController::class, 'current']);
    });

    // -- POS TRANSACTIONS --
    Route::post('/pos/checkout', [TransactionController::class, 'store']);
});
