<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GameController;
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

    Route::post('/paystack/initialize', [PayGatewayController::class, 'paystackInitialize']);
    Route::get('/paystack/callback', [PayGatewayController::class, 'paystackCallback'])->name('paystack.callback');

    Route::post('/deposit', [TransactionController::class, 'deposit']);
    Route::post('/withdraw', [TransactionController::class, 'withdraw']);
    Route::post('/spend-game', [TransactionController::class, 'spendOnGame']);
});
