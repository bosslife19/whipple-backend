<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;

Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'loginWeb']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPasswordForm'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');

// Admin Routes
Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');

    Route::get('/forecast', [AdminController::class, 'forecast'])->name('admin.forecast');
    Route::post('/forecast', [AdminController::class, 'storeForecastMatch'])->name('admin.forecast.store');
    Route::put('/forecast/{id}', [AdminController::class, 'updateForecastMatch'])->name('admin.forecast.update');
    Route::post('/upload-result', [AdminController::class, 'uploadResult'])->name('admin.upload-result');
    Route::get('/export-forecast-matches', [AdminController::class, 'exportForecastMatches'])->name('admin.forecast.export');
    Route::get('/forecast-template', [AdminController::class, 'exportForecastTemplate'])->name('admin.forecast.template');
    Route::post('/forecast-template', [AdminController::class, 'importForecastMatches'])->name('admin.forecast.import');
});

