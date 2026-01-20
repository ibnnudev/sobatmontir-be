<?php

use App\Http\Controllers\SosController;
use Illuminate\Support\Facades\Route;

Route::prefix('sos')->group(function () {
    // Customer
    Route::post('/request', [SosController::class, 'requestSos']);
    Route::get('/my-active', [SosController::class, 'myActiveOrder']); // Untuk tracking status

    // Mechanic
    Route::post('/nearby', [SosController::class, 'nearby']); // Mekanik cari order
    Route::post('/{id}/accept', [SosController::class, 'accept']);
    Route::post('/{id}/status', [SosController::class, 'updateStatus']);
});
