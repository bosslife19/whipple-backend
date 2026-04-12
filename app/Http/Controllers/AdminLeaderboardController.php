<?php

namespace App\Http\Controllers;

use App\Http\Services\LeaderboardCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminLeaderboardController extends Controller
{
    public function __construct(
        protected LeaderboardCalculationService $leaderboards
    ) {}

    public function reset(Request $request): JsonResponse
    {
        $request->validate([
            'label' => 'nullable|string|max:255',
        ]);

        $period = $this->leaderboards->resetWeeklyPeriod($request->input('label'));

        return response()->json([
            'status' => true,
            'message' => 'Leaderboard period reset.',
            'period' => $period,
        ]);
    }
}
