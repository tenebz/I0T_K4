<?php

use App\Http\Controllers\Api\PercobaanController;
use App\Http\Controllers\Api\PerangkatController;
use App\Http\Middleware\PastikanKunciIot;
use Illuminate\Support\Facades\Route;

// Dipanggil dari dashboard (browser).
Route::post('/sinkron', [PerangkatController::class, 'sinkron'])->name('api.sinkron');
Route::post('/perangkat/daya', [PerangkatController::class, 'daya'])->name('api.daya');

// Dipanggil dari alat IoT (ESP32/ESP8266).
Route::middleware(PastikanKunciIot::class)->group(function () {
    Route::post('/perangkat/ping', [PerangkatController::class, 'ping'])->name('api.ping');
    Route::post('/percobaan', [PercobaanController::class, 'store'])->name('api.percobaan.store');
});
