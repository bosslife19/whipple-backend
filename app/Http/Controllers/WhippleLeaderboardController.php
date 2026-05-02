<?php

namespace App\Http\Controllers;

use App\Services\LeaderboardService;
use Illuminate\Http\Request;

class WhippleLeaderboardController extends Controller
{
    public function __construct(
        protected LeaderboardService $leaderboard
    ) {}

    public function index(Request $request)
    {
        $weekId = $request->get('week_id');
        $weeks = $this->leaderboard->getHistoricalWeeks();
        $currentWeek = $weekId ? \App\Models\LeaderboardWeek::find($weekId) : \App\Models\LeaderboardWeek::current();

        $raw = $this->leaderboard->computeScoresForAllUsers($weekId);
        $hydrated = $this->leaderboard->hydrateUserRows($raw, $weekId);

        $frequentBoard = $this->leaderboard->buildBoard($hydrated, 'frequent')->take(LeaderboardService::TOP_DISPLAY);
        $winsBoard = $this->leaderboard->buildBoard($hydrated, 'wins')->take(LeaderboardService::TOP_DISPLAY);

        [$pStart, $pEnd] = $this->leaderboard->getPeriodBounds($weekId);
        $weekStatus = $this->leaderboard->getWeekStatus($weekId);

        $authId = $request->user()->id;
        $userFrequent = $frequentBoard->firstWhere('user_id', $authId);
        $userWins = $winsBoard->firstWhere('user_id', $authId);

        if (!$userFrequent || !$userWins) {
            [$from, $to] = $this->leaderboard->periodCarbonBounds($weekId);
            $single = $this->leaderboard->computeScoresForUser($authId, $from, $to, $weekId);
            $wrapped = $this->leaderboard->hydrateUserRows(collect([array_merge($single, ['user_id' => $authId])]), $weekId)->first();
            
            if (!$userFrequent) {
                $rankF = $frequentBoard->count() + 1;
                $userFrequent = array_merge($wrapped ?? [], ['rank' => $rankF, 'highlight_tournament' => false]);
            } else {
                $userFrequent = $this->normalizeLeaderboardRow($userFrequent);
            }
            if (!$userWins) {
                $rankW = $winsBoard->count() + 1;
                $userWins = array_merge($wrapped ?? [], ['rank' => $rankW, 'highlight_tournament' => false]);
            } else {
                $userWins = $this->normalizeLeaderboardRow($userWins);
            }
        } else {
            $userFrequent = $this->normalizeLeaderboardRow($userFrequent);
            $userWins = $this->normalizeLeaderboardRow($userWins);
        }

        $rankF = $userFrequent['rank'] ?? null;
        $rankW = $userWins['rank'] ?? null;

        [$from, $to] = $this->leaderboard->periodCarbonBounds($weekId);
        $qual = $this->leaderboard->qualification(
            $authId,
            $from,
            $to,
            $userFrequent['breakdown'] ?? []
        );

        $prompts = $this->leaderboard->promptsForUser(
            $authId,
            (int) $rankF,
            (int) $rankW,
            (float) ($userFrequent['frequent'] ?? 0),
            (float) ($userWins['wins'] ?? 0),
            $qual,
            $frequentBoard,
            $winsBoard,
            $currentWeek?->top_rank ?? 32
        );

        return response()->json([
            'status' => true,
            'period' => [
                'label' => $currentWeek?->label,
                'start' => $pStart,
                'end' => $pEnd,
            ],
            'week_status' => $weekStatus,
            'weeks' => $weeks->map(fn($w) => [
                'id' => $w->id,
                'label' => $w->label,
                'start' => $w->start_date->format('M d'),
                'end' => $w->end_date->format('M d'),
                'is_current' => $w->is_current,
            ]),
            'most_frequent' => $frequentBoard->values(),
            'most_wins' => $winsBoard->values(),
            'me' => [
                'frequent' => $userFrequent,
                'wins' => $userWins,
                'prompts' => $prompts,
            ],
            'meta' => [
                'top_display' => LeaderboardService::TOP_DISPLAY,
                'top_rank' => $currentWeek->top_rank ?? 32,
                'tournament_cutoff' => LeaderboardService::TOURNAMENT_CUTOFF,
            ],
        ]);
    }

    /**
     * @param  array|\Illuminate\Contracts\Support\Arrayable  $row
     */
    private function normalizeLeaderboardRow(mixed $row): array
    {
        if (is_array($row)) {
            return $row;
        }

        if (is_object($row) && method_exists($row, 'toArray')) {
            return $row->toArray();
        }

        return (array) $row;
    }
}
