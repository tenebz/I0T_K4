<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

// Dashboard tidak memakai session maupun form (semua aksi lewat /api yang stateless),
// jadi middleware session dilepas: tidak ada lagi penulisan baris session ke database
// di setiap kunjungan halaman.
Route::get('/', DashboardController::class)
    ->name('dashboard')
    ->withoutMiddleware([StartSession::class, ShareErrorsFromSession::class, PreventRequestForgery::class]);
