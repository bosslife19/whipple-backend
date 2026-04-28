<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Admin\LeaderboardAdminController;
use App\Http\Controllers\Admin\TournamentAdminController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminSettingController;

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
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');

    Route::get('/forecast', [AdminController::class, 'forecast'])->name('admin.forecast');
    Route::post('/forecast', [AdminController::class, 'storeForecastMatch'])->name('admin.forecast.store');
    Route::put('/forecast/{id}', [AdminController::class, 'updateForecastMatch'])->name('admin.forecast.update');
    Route::post('/upload-result', [AdminController::class, 'uploadResult'])->name('admin.upload-result');
    Route::get('/export-forecast-matches', [AdminController::class, 'exportForecastMatches'])->name('admin.forecast.export');
    Route::get('/forecast-template', [AdminController::class, 'exportForecastTemplate'])->name('admin.forecast.template');
    Route::post('/forecast-template', [AdminController::class, 'importForecastMatches'])->name('admin.forecast.import');

    Route::get('/leaderboard', [LeaderboardAdminController::class, 'index'])->name('admin.leaderboard');
    Route::post('/leaderboard/reset', [LeaderboardAdminController::class, 'reset'])->name('admin.leaderboard.reset');
    Route::post('/leaderboard/week', [LeaderboardAdminController::class, 'createWeek'])->name('admin.leaderboard.create-week');
    Route::post('/leaderboard/week/update', [LeaderboardAdminController::class, 'updateWeek'])->name('admin.leaderboard.update-week');
    Route::post('/leaderboard/pause', [LeaderboardAdminController::class, 'togglePause'])->name('admin.leaderboard.pause');
    Route::post('/leaderboard/virtual-players', [LeaderboardAdminController::class, 'generateVirtualPlayers'])->name('admin.leaderboard.virtual-players');

    Route::get('/tournament', [TournamentAdminController::class, 'index'])->name('admin.tournament');
    Route::post('/tournament', [TournamentAdminController::class, 'store'])->name('admin.tournament.store');
    Route::post('/tournament/update', [TournamentAdminController::class, 'update'])->name('admin.tournament.update');
    Route::post('/tournament/activate', [TournamentAdminController::class, 'activate'])->name('admin.tournament.activate');
    Route::post('/tournament/complete', [TournamentAdminController::class, 'complete'])->name('admin.tournament.complete');
    Route::post('/tournament/import', [TournamentAdminController::class, 'import'])->name('admin.tournament.import');
    Route::post('/tournament/add-player', [TournamentAdminController::class, 'addPlayer'])->name('admin.tournament.add-player');
    Route::post('/tournament/remove-player', [TournamentAdminController::class, 'removePlayer'])->name('admin.tournament.remove-player');
    Route::post('/tournament/lobby', [TournamentAdminController::class, 'createLobby'])->name('admin.tournament.lobby');
    Route::post('/tournament/lobby/players', [TournamentAdminController::class, 'lobbyPlayers'])->name('admin.tournament.lobby-players');
    Route::post('/tournament/lobby/countdown', [TournamentAdminController::class, 'startCountdown'])->name('admin.tournament.lobby-countdown');
    Route::post('/tournament/lobby/end', [TournamentAdminController::class, 'endLobby'])->name('admin.tournament.lobby-end');
    Route::post('/tournament/eliminate', [TournamentAdminController::class, 'eliminate'])->name('admin.tournament.eliminate');
    Route::post('/tournament/commentary', [TournamentAdminController::class, 'commentary'])->name('admin.tournament.commentary');
    Route::post('/tournament/stream-slot', [TournamentAdminController::class, 'streamSlot'])->name('admin.tournament.stream-slot');
    Route::post('/tournament/reset', [TournamentAdminController::class, 'reset'])->name('admin.tournament.reset');

    // User Management
    Route::get('/users', [AdminUserController::class, 'index'])->name('admin.users.index');
    Route::get('/users/{id}', [AdminUserController::class, 'show'])->name('admin.users.show');
    Route::post('/users/{id}/update-balance', [AdminUserController::class, 'updateBalance'])->name('admin.users.update-balance');
    Route::post('/users/{id}/update-points', [AdminUserController::class, 'updatePoints'])->name('admin.users.update-points');

    // Transactions
    Route::get('/transactions', [AdminUserController::class, 'transactions'])->name('admin.transactions.index');

    // Admin Settings
    Route::get('/settings', [AdminSettingController::class, 'index'])->name('admin.settings.index');
    Route::post('/settings/password', [AdminSettingController::class, 'updatePassword'])->name('admin.settings.password');
    Route::post('/settings/admins', [AdminSettingController::class, 'storeAdmin'])->name('admin.settings.admins.store');
    Route::put('/settings/admins/{id}', [AdminSettingController::class, 'updateAdmin'])->name('admin.settings.admins.update');
    Route::delete('/settings/admins/{id}', [AdminSettingController::class, 'deleteAdmin'])->name('admin.settings.admins.delete');
});

