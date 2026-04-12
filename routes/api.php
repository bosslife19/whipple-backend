<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VoteController;
use App\Http\Controllers\ForecastController;
use App\Http\Controllers\SkillgameController;
use App\Http\Controllers\PayGatewayController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\TournamentController;
use App\Http\Controllers\AdminTournamentController;
use App\Http\Controllers\AdminLeaderboardController;
use App\Http\Controllers\AdminUserBalanceController;
use App\Http\Controllers\AdminStatsController;

Route::get('/leaderboards/most-frequent', [LeaderboardController::class, 'mostFrequent']);
Route::get('/leaderboards/most-wins', [LeaderboardController::class, 'mostWins']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/signup', [AuthController::class, 'signup']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendCode']);
Route::post('/verify-reset-code', [ForgotPasswordController::class, 'verifyCode']);
Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword']);
Route::post('/korapay/webhook', [TransactionController::class, 'handle']);



Route::middleware('auth:sanctum')->group(function () {
    Route::get('/leaderboards/me', [LeaderboardController::class, 'me']);

    Route::prefix('tournaments')->group(function () {
        Route::get('/mine', [TournamentController::class, 'mine']);
        Route::get('/{uuid}', [TournamentController::class, 'show']);
        Route::post('/{uuid}/screen-share', [TournamentController::class, 'enableScreenShare']);
        Route::post('/{uuid}/rounds/{roundId}/score', [TournamentController::class, 'submitRoundScore']);
        Route::get('/{uuid}/rounds/{roundId}/sync', [TournamentController::class, 'syncRound']);
    });

    Route::middleware(['admin', 'admin.permission:leaderboards'])->prefix('admin')->group(function () {
        Route::post('/leaderboard/reset', [AdminLeaderboardController::class, 'reset']);
    });

    Route::middleware(['admin', 'admin.permission:analytics'])->prefix('admin')->group(function () {
        Route::get('/stats/summary', [AdminStatsController::class, 'summary']);
    });

    Route::middleware(['admin', 'admin.permission:users'])->prefix('admin')->group(function () {
        Route::post('/users/{id}/balance', [AdminUserBalanceController::class, 'adjust']);
    });

    Route::middleware(['admin', 'admin.permission:tournaments'])->prefix('admin')->group(function () {
        Route::post('/tournaments', [AdminTournamentController::class, 'store']);
        Route::post('/tournaments/{id}/import', [AdminTournamentController::class, 'import']);
        Route::post('/tournaments/{id}/players', [AdminTournamentController::class, 'addPlayer']);
        Route::delete('/tournaments/{id}/players/{userId}', [AdminTournamentController::class, 'removePlayer']);
        Route::post('/tournaments/{id}/rounds', [AdminTournamentController::class, 'createRound']);
        Route::post('/tournaments/rounds/{roundId}/countdown', [AdminTournamentController::class, 'startCountdown']);
        Route::post('/tournaments/rounds/{roundId}/end', [AdminTournamentController::class, 'endRound']);
        Route::post('/tournaments/{id}/eliminate', [AdminTournamentController::class, 'eliminate']);
        Route::post('/tournaments/{id}/commentary', [AdminTournamentController::class, 'commentary']);
        Route::post('/tournaments/{id}/screen-share', [AdminTournamentController::class, 'screenShare']);
        Route::post('/tournaments/{id}/reset', [AdminTournamentController::class, 'reset']);
        Route::get('/tournaments/{id}/state', [AdminTournamentController::class, 'state']);
    });

    Route::post('/create-game', [GameController::class, 'createGame']);
    Route::get('/get-all-games', [GameController::class, 'getAllGames']);
    Route::post("/submit-vote", [VoteController::class, 'submitVote']);
    Route::get("/get-game/{id}", [GameController::class, 'getGame']);
    Route::post('/play-game', [GameController::class, 'playGame']);
    Route::post('/play-losers-game', [GameController::class, 'playLosersGame']);
    Route::get('/get-losers-game', [GameController::class, 'getLosersGame']);
    Route::post('/win-losers-game', [GameController::class, "winLosersGame"]);
    Route::post('/losers-vote', [VoteController::class, 'losersVote']);
    Route::post('/user-push-token', [UserController::class, 'setPushToken']);
    Route::post('/send-push-notifications', [UserController::class, 'sendPushNotifications']);

    Route::post('/paystack/initialize', [PayGatewayController::class, 'paystackInitialize']);
    Route::get('/paystack/callback', [PayGatewayController::class, 'paystackCallback'])->name('paystack.callback');
    Route::get("/get-my-games", [GameController::class, 'getMyGames']);
    Route::post('/deposit/initialize', [TransactionController::class, 'depositInitialize']);
    Route::post('/deposit/verified', [TransactionController::class, 'depositVerified']);
    Route::post('/withdraw/request', [TransactionController::class, 'withdrawRequest']);
    Route::post('/spend-game', [TransactionController::class, 'spendOnGame']);
    Route::get('/resend-otp', [AuthController::class, 'resendOtp']);

    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/update-profile', [UserController::class, 'updateProfile']);

    Route::get('/transaction-list/{type?}', [TransactionController::class, 'transactionList']);
    Route::post('/transaction-pin', [TransactionController::class, 'transactionPin']);
    Route::get('/get-my-played-games', [GameController::class, 'getMyPlayedGames']);
    Route::get('/paystack/getbank', [PayGatewayController::class, 'getBanks']);
    Route::post('/paystack/initialize', [PayGatewayController::class, 'paystackInitialize']);
    Route::get('/paystack/callback', [PayGatewayController::class, 'paystackCallback'])->name('paystack.callback');
    Route::post('/paystack/withdraw/resolve', [PayGatewayController::class, 'resolveAccount']);
    Route::post('/paystack/withdraw/recipient', [PayGatewayController::class, 'createRecipient']);
    Route::post('/paystack/withdraw/initiate', [PayGatewayController::class, 'initiateTransfer']);
    Route::post('/deduct-balance', [UserController::class, 'deductBalance']);
    Route::get('/leaderboard', [GameController::class, 'leaderboard']);

    Route::get('/referral-list', [UserController::class, 'referralList']);
    Route::get('/admin/parameter', [UserController::class, 'adminParameter']);

    Route::post('/bank-save', [UserController::class, 'bankSave']);
    Route::get('/bank-list', [UserController::class, 'bankList']);
    // routes/api.php



    Route::get('/quiz/start', [QuizController::class, 'start']);
    Route::post('/quiz/answer', [QuizController::class, 'answer']);
    Route::post('/quiz/boost', [QuizController::class, 'boost']);
    Route::post('/quiz/complete', [QuizController::class, 'complete']);
    Route::post('/quiz/close', [QuizController::class, 'close']);

    Route::prefix('skillgame')->group(function () {
        Route::get('/games', [SkillgameController::class, 'index']);
        Route::get('/games/{key}', [SkillgameController::class, 'show']);

        // Matchmaking / Matches
        Route::get('/matches/join/{key}', [SkillgameController::class, 'join']); // expects { game_key, user_id }
        Route::get('/matches/status/{id}', [SkillgameController::class, 'status']);
        Route::get('/matches/start/{id}', [SkillgameController::class, 'start']);
        Route::post('/matches/updateScore', [SkillgameController::class, 'updateScore']);
        Route::post('/matches/complete', [SkillgameController::class, 'complete']);
        Route::get('/matches/checkStatus/{id}', [SkillgameController::class, 'checkStatus']);
        Route::post('/matches/{match}/leave', [SkillgameController::class, 'leave']);
        Route::get('/matches/{match}', [SkillgameController::class, 'showMatch']);

        // Game play (client submits final results)
        Route::post('/matches/{match}/submit', [SkillgameController::class, 'submitResult']);

        // Admin / debugging
        Route::post('/matches/{match}/force_start', [SkillgameController::class, 'forceStart']);
    });

    Route::prefix('forecast')->group(function () {

        Route::get('type/{type?}', [ForecastController::class, 'list']);
        Route::post('/submit', [ForecastController::class, 'submit']);

        Route::get('/myForecasts', [ForecastController::class, 'myForecasts']);
    });

});
