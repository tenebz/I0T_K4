<?php

use App\Http\Controllers\Api\PerangkatController;
use App\Http\Controllers\Api\PercobaanController;
use App\Http\Controllers\Api\TrialController;
use App\Http\Middleware\PastikanKunciIot;
use Illuminate\Support\Facades\Route;

// Dipanggil dari dashboard (browser).
Route::post('/sinkron', [PerangkatController::class, 'sinkron'])->name('api.sinkron');
Route::post('/perangkat/daya', [PerangkatController::class, 'daya'])->name('api.daya');

// Trial management (browser).
Route::post('/trial/start', [TrialController::class, 'start'])->name('api.trial.start');
Route::post('/trial/end', [TrialController::class, 'end'])->name('api.trial.end');
Route::get('/trial/status', [TrialController::class, 'status'])->name('api.trial.status');
Route::get('/trial/history', [TrialController::class, 'history'])->name('api.trial.history');
Route::get('/trial/{trial}', [TrialController::class, 'show'])->whereNumber('trial')->name('api.trial.show');
Route::delete('/trial/{trial}', [TrialController::class, 'destroy'])->whereNumber('trial')->name('api.trial.destroy');

// Dipanggil dari alat IoT (ESP32/ESP8266).
Route::middleware(PastikanKunciIot::class)->group(function () {
    Route::post('/perangkat/ping', [PerangkatController::class, 'ping'])->name('api.ping');
    Route::post('/percobaan', [PercobaanController::class, 'store'])->name('api.percobaan.store');
});
