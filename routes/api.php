<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\SosController;
use App\Http\Controllers\TrackingController;
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

    // -- SOS FEATURE --
    Route::prefix('sos')->group(function () {
        // Customer
        Route::post('/request', [SosController::class, 'requestSos']);
        Route::get('/my-active', [SosController::class, 'myActiveOrder']); // Untuk tracking status

        // Mechanic
        Route::post('/nearby', [SosController::class, 'nearby']); // Mekanik cari order
        Route::post('/{id}/accept', [SosController::class, 'accept']);
        Route::post('/{id}/status', [SosController::class, 'updateStatus']);
        Route::post('/{id}/finalize', [SosController::class, 'finalize']);
    });

    // -- TRACKING FEATURE --
    Route::prefix('tracking')->group(function () {
        Route::post('/update', [TrackingController::class, 'updateLocation']);
        Route::get('/{orderId}', [TrackingController::class, 'trackOrder']);
    });

    // -- QUEUE SYSTEM --
    Route::prefix('queue')->group(function () {
        // Customer
        Route::get('/workshop/{workshopId}/status', [QueueController::class, 'checkStatus']);
        Route::post('/book', [QueueController::class, 'book']);
        Route::get('/my-ticket', [QueueController::class, 'myTicket']);

        // Mechanic
        Route::post('/serve', [QueueController::class, 'serve']);

        // TV Display
        Route::get('/workshop/{workshopId}/display', [QueueController::class, 'display']);
    });
});
