<?php

namespace App\Http\Controllers;

use App\Http\Services\LeaderboardCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    public function __construct(
        protected LeaderboardCalculationService $leaderboards
    ) {}

    public function mostFrequent(): JsonResponse
    {
        $rows = $this->leaderboards->buildLeaderboard('most_frequent', 100);

        return response()->json([
            'status' => true,
            'type' => 'most_frequent',
            'leaderboard' => $rows->map(fn (array $r) => $this->publicRow($r))->values(),
        ]);
    }

    public function mostWins(): JsonResponse
    {
        $rows = $this->leaderboards->buildLeaderboard('most_wins', 100);

        return response()->json([
            'status' => true,
            'type' => 'most_wins',
            'leaderboard' => $rows->map(fn (array $r) => $this->publicRow($r))->values(),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $prompts = $this->leaderboards->qualificationPrompts($user);

        return response()->json([
            'status' => true,
            'data' => $prompts,
        ]);
    }

    protected function publicRow(array $r): array
    {
        return [
            'rank' => $r['rank'],
            'user_id' => $r['user_id'],
            'name' => $r['name'],
            'score' => $r['score'],
            'qualified' => $r['qualified'] ? 'QUALIFIED' : 'NOT QUALIFIED',
            'qualified_bool' => $r['qualified'],
            'tournament_spotlight' => $r['tournament_cutline'] && $r['qualified'],
            'highlight_top_32' => $r['tournament_cutline'],
        ];
    }
}
