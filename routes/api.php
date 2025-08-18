<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VoteController;
use App\Http\Controllers\PayGatewayController;
use App\Http\Controllers\TransactionController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/signup', [AuthController::class, 'signup']);
Route::post('/login', [AuthController::class, 'login']);


Route::middleware('auth:sanctum')->group(function(){
   Route::post('/create-game', [GameController::class, 'createGame']);
   Route::get('/get-all-games', [GameController::class, 'getAllGames']);
   Route::post("/submit-vote", [VoteController::class, 'submitVote']);
   Route::get("/get-game/{id}", [GameController::class, 'getGame']);
   Route::post('/play-game', [GameController::class, 'playGame']);
   Route::post('/play-losers-game', [GameController::class, 'playLosersGame']);
   Route::get('/get-losers-game', [GameController::class, 'getLosersGame']);
   Route::post('/win-losers-game', [GameController::class, "winLosersGame"]);
   Route::post('/losers-vote', [VoteController::class, 'losersVote']);
   Route::post('/paystack/initialize', [PayGatewayController::class, 'paystackInitialize']);
    Route::get('/paystack/callback', [PayGatewayController::class, 'paystackCallback'])->name('paystack.callback');
    Route::get("/get-my-games", [GameController::class, 'getMyGames']);
    Route::post('/deposit/initialize', [TransactionController::class, 'depositInitialize']);
    Route::post('/deposit/verified', [TransactionController::class, 'depositVerified']);
    Route::post('/withdraw', [TransactionController::class, 'withdraw']);
    Route::post('/spend-game', [TransactionController::class, 'spendOnGame']);

    Route::post('/transaction-pin', [TransactionController::class, 'transactionPin']);
    Route::get('/get-my-played-games', [GameController::class, 'getMyPlayedGames']);

    Route::get('/referral-list', [UserController::class, 'referralList']);

});
