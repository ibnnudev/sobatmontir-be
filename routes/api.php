<?php

use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // 1. Product Management
    Route::apiResource('products', ProductController::class);

    // 2. Inventory Management (Stock Opname)
    Route::post('/inventory/adjust', [InventoryController::class, 'adjust']);
});