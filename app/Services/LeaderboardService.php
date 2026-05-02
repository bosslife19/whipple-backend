<?php

namespace App\Services;

use App\Models\LeaderboardWeek;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LeaderboardService
{
    public const TOP_DISPLAY = 100;

    public const TOURNAMENT_CUTOFF = 32;

    public const REFERRAL_FREQUENT_POINTS = 5;

    public const MIN_QUALIFYING_DEPOSIT_AMOUNT = 500;

    /** Skill game keys used for qualification counts */
    public const SKILL_KEYS = ['tap_rush', 'math_clash', 'color_switch', 'defuse_x'];

    public function getPeriodBounds(?int $weekId = null): array
    {
        $week = $weekId ? LeaderboardWeek::find($weekId) : LeaderboardWeek::current();
        if (!$week) {
            $start = Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateTimeString();
            $end = Carbon::now()->endOfWeek(Carbon::SUNDAY)->toDateTimeString();

            return [$start, $end];
        }

        return [$week->start_date->toDateTimeString(), $week->end_date->toDateTimeString()];
    }

    public function getWeekStatus(?int $weekId = null): array
    {
        $week = $weekId ? LeaderboardWeek::find($weekId) : LeaderboardWeek::current();
        
        if (!$week) {
            return [
                'is_active' => false,
                'reason' => 'no_week',
            ];
        }

        $now = Carbon::now();

        if ($now->lt($week->start_date)) {
            return [
                'is_active' => false,
                'reason' => 'not_started',
            ];
        }

        if ($now->gt($week->end_date) || $week->status === 'completed') {
            return [
                'is_active' => false,
                'reason' => 'ended',
            ];
        }

        if ($week->status === 'paused') {
            return [
                'is_active' => false,
                'reason' => 'paused',
            ];
        }

        return [
            'is_active' => true,
            'reason' => 'active',
        ];
    }

    public function periodCarbonBounds(?int $weekId = null): array
    {
        if ($weekId) {
            $week = LeaderboardWeek::find($weekId);
            if ($week) {
                return [
                    $week->start_date->startOfDay(),
                    $week->end_date->endOfDay(),
                ];
            }
        }

        [$s, $e] = $this->getPeriodBounds();

        return [
            Carbon::parse($s)->startOfDay(),
            Carbon::parse($e)->endOfDay(),
        ];
    }

    /**
     * @return Collection<int, array{frequent: float, wins: float, user_id: int, breakdown: array}>
     */
    public function computeScoresForAllUsers(?int $weekId = null): Collection
    {
        [$from, $to] = $this->periodCarbonBounds($weekId);

        $userIds = $this->collectRelevantUserIds($from, $to, $weekId);
        $rows = [];
        foreach ($userIds as $uid) {
            $rows[$uid] = $this->computeScoresForUser((int) $uid, $from, $to, $weekId);
        }

        return collect($rows)->map(fn ($r, $uid) => array_merge($r, ['user_id' => (int) $uid]));
    }

    /**
     * @return array{frequent: float, wins: float, breakdown: array}
     */
    public function computeScoresForUser(int $userId, Carbon $from, Carbon $to, ?int $weekId = null): array
    {
        $skillRounds = $this->skillGameRounds($userId, $from, $to, $weekId);
        $skillWins = $this->skillGameWins($userId, $from, $to, $weekId);
        $quiz = $this->quizStats($userId, $from, $to, $weekId);
        $forecast = $this->forecastStats($userId, $from, $to, $weekId);
        $depositPts = $this->depositPoints($userId, $from, $to, $weekId);
        $withdrawalPenalty = $this->withdrawalPenalty($userId, $from, $to, $weekId);
        $referralCount = $this->referralCount($userId, $from, $to, $weekId);
        $referralPoints = $referralCount * self::REFERRAL_FREQUENT_POINTS;

        $freqSkill = 0.0;
        $winSkill = 0.0;
        $weightsF = ['tap_rush' => 1, 'math_clash' => 2, 'color_switch' => 3, 'defuse_x' => 4];
        $weightsW = ['tap_rush' => 1.5, 'math_clash' => 2.5, 'color_switch' => 3.5, 'defuse_x' => 4.5];
        foreach (self::SKILL_KEYS as $key) {
            $r = (int) ($skillRounds[$key] ?? 0);
            $w = (int) ($skillWins[$key] ?? 0);
            $freqSkill += $weightsF[$key] * $r;
            $winSkill += $weightsW[$key] * $w;
        }

        $freqQuiz = 1.0 * $quiz['answered'];
        $winQuiz = 1.5 * $quiz['correct'];

        $freqForecast = 1.0 * ($forecast['general'] + $forecast['specific']);
        $winForecast = 1.5 * ($forecast['general_correct'] + $forecast['specific_correct']);

        $frequent = $freqSkill + $freqQuiz + $freqForecast + $depositPts + $withdrawalPenalty + $referralPoints;
        $wins = $winSkill + $winQuiz + $winForecast + $withdrawalPenalty;

        $breakdown = [
            'skill_rounds' => $skillRounds,
            'skill_wins' => $skillWins,
            'quiz_answered' => $quiz['answered'],
            'quiz_correct' => $quiz['correct'],
            'forecast_general' => $forecast['general'],
            'forecast_specific' => $forecast['specific'],
            'forecast_general_correct' => $forecast['general_correct'],
            'forecast_specific_correct' => $forecast['specific_correct'],
            'deposit_points' => $depositPts,
            'withdrawal_penalty' => $withdrawalPenalty,
            'referral_count' => $referralCount,
            'referral_points' => $referralPoints,
        ];

        return [
            'frequent' => round($frequent, 4),
            'wins' => round($wins, 4),
            'breakdown' => $breakdown,
        ];
    }

    public function qualification(int $userId, Carbon $from, Carbon $to, array $breakdown): array
    {
        $skillRounds = $breakdown['skill_rounds'] ?? [];
        $skillOk = true;
        foreach (self::SKILL_KEYS as $key) {
            if ((int) ($skillRounds[$key] ?? 0) < 3) {
                $skillOk = false;
                break;
            }
        }

        $quizSessions = $this->quizSessionCount($userId, $from, $to);
        $quizOk = $quizSessions >= 3;

        $gen = (int) ($breakdown['forecast_general'] ?? 0);
        $spec = (int) ($breakdown['forecast_specific'] ?? 0);
        $forecastGeneralOk = $gen >= 3;
        $forecastSpecificOk = $spec >= 3;

        $deposits = $this->completedDepositCount($userId, $from, $to, $breakdown['week_id'] ?? null);
        $depositOk = $deposits >= 3;

        $qualified = $skillOk && $quizOk && $forecastGeneralOk && $forecastSpecificOk && $depositOk;

        return [
            'qualified' => $qualified,
            'requirements' => [
                'skill_games_min_3_each' => [
                    'met' => $skillOk,
                    'per_game' => array_map(fn ($k) => [
                        'key' => $k,
                        'played' => (int) ($skillRounds[$k] ?? 0),
                        'need' => 3,
                    ], self::SKILL_KEYS),
                ],
                'quiz_min_3_sessions' => [
                    'met' => $quizOk,
                    'sessions' => $quizSessions,
                    'need' => 3,
                ],
                'forecast_general_min_3' => ['met' => $forecastGeneralOk, 'count' => $gen, 'need' => 3],
                'forecast_specific_min_3' => ['met' => $forecastSpecificOk, 'count' => $spec, 'need' => 3],
                'deposits_min_3' => [
                    'met' => $depositOk,
                    'count' => $deposits,
                    'need' => 3,
                    'min_amount' => self::MIN_QUALIFYING_DEPOSIT_AMOUNT,
                ],
            ],
        ];
    }


    public function resetWeeklyPeriod(): LeaderboardWeek
    {
        $nextMonday = Carbon::now()->addWeek()->startOfWeek(Carbon::MONDAY);
        $nextSunday = Carbon::now()->addWeek()->endOfWeek(Carbon::SUNDAY);

        // Deactivate current
        LeaderboardWeek::where('is_current', true)->update(['is_current' => false, 'status' => 'completed']);

        return LeaderboardWeek::create([
            'label' => 'Week of ' . $nextMonday->format('Y-m-d'),
            'start_date' => $nextMonday,
            'end_date' => $nextSunday,
            'status' => 'active',
            'is_current' => true,
        ]);
    }

    private function applyPauseFilters($query, Carbon $from, Carbon $to, ?int $weekId = null, string $column = 'created_at')
    {
        $week = $weekId ? LeaderboardWeek::find($weekId) : LeaderboardWeek::current();
        if (!$week) return $query;

        $pauses = $week->pauses()->get();
        foreach ($pauses as $pause) {
            $pStart = $pause->paused_at;
            $pEnd = $pause->resumed_at ?? Carbon::now();
            $query->whereNotBetween($column, [$pStart, $pEnd]);
        }

        return $query;
    }

    private function collectRelevantUserIds(Carbon $from, Carbon $to, ?int $weekId = null): array
    {
        $fromSkill = $this->applyPauseFilters(
            DB::table('skill_game_match_players as mp')
                ->join('skill_game_matches as m', 'm.id', '=', 'mp.match_id')
                ->where('mp.is_demo', false)
                ->where('m.status', 'finished')
                ->whereRaw('COALESCE(m.finished_at, m.updated_at) BETWEEN ? AND ?', [$from, $to]),
            $from, $to, $weekId, 'mp.created_at'
        )->distinct()->pluck('mp.user_id');

        $fromQuiz = $this->applyPauseFilters(
            DB::table('quiz_sessions')
                ->whereBetween('created_at', [$from, $to]),
            $from, $to, $weekId
        )->distinct()->pluck('user_id');

        $fromFc = $this->applyPauseFilters(
            DB::table('forecasts')
                ->whereBetween('created_at', [$from, $to]),
            $from, $to, $weekId
        )->distinct()->pluck('user_id');

        $fromTx = $this->applyPauseFilters(
            DB::table('transactions')
                ->whereIn('type', ['deposit', 'withdrawal'])
                ->whereBetween('created_at', [$from, $to]),
            $from, $to, $weekId
        )->distinct()->pluck('user_id');

        $fromReferrals = $this->applyPauseFilters(
            User::query()
                ->whereNotNull('referred_by')
                ->whereBetween('created_at', [$from, $to]),
            $from, $to, $weekId
        )->distinct()->pluck('referred_by');

        return array_values(array_unique(array_merge(
            $fromSkill->all(),
            $fromQuiz->all(),
            $fromFc->all(),
            $fromTx->all(),
            $fromReferrals->all()
        )));
    }

    /** @return array<string,int> */
    private function skillGameRounds(int $userId, Carbon $from, Carbon $to, ?int $weekId = null): array
    {
        $keys = self::SKILL_KEYS;
        $out = array_fill_keys($keys, 0);
        $query = DB::table('skill_game_match_players as mp')
            ->join('skill_game_matches as m', 'm.id', '=', 'mp.match_id')
            ->join('skill_games as g', 'g.id', '=', 'm.game_id')
            ->where('mp.user_id', $userId)
            ->where('mp.is_demo', false)
            ->where('mp.status', 'finished')
            ->where('m.status', 'finished')
            ->whereIn('g.key', $keys)
            ->whereRaw('COALESCE(m.finished_at, m.updated_at) BETWEEN ? AND ?', [$from, $to]);

        $rows = $this->applyPauseFilters($query, $from, $to, $weekId, 'mp.created_at')
            ->groupBy('g.key')
            ->select('g.key', DB::raw('COUNT(*) as c'))
            ->get();
        foreach ($rows as $r) {
            $out[$r->key] = (int) $r->c;
        }

        return $out;
    }

    /** @return array<string,int> */
    private function skillGameWins(int $userId, Carbon $from, Carbon $to, ?int $weekId = null): array
    {
        $keys = self::SKILL_KEYS;
        $out = array_fill_keys($keys, 0);
        $query = DB::table('skill_game_match_players as mp')
            ->join('skill_game_matches as m', 'm.id', '=', 'mp.match_id')
            ->join('skill_games as g', 'g.id', '=', 'm.game_id')
            ->where('mp.user_id', $userId)
            ->where('mp.is_demo', false)
            ->where('mp.status', 'finished')
            ->where('m.status', 'finished')
            ->where('mp.rank', 1)
            ->where('mp.score', '>', 0)
            ->whereIn('g.key', $keys)
            ->whereRaw('COALESCE(m.finished_at, m.updated_at) BETWEEN ? AND ?', [$from, $to]);

        $rows = $this->applyPauseFilters($query, $from, $to, $weekId, 'mp.created_at')
            ->groupBy('g.key')
            ->select('g.key', DB::raw('COUNT(*) as c'))
            ->get();
        foreach ($rows as $r) {
            $out[$r->key] = (int) $r->c;
        }

        return $out;
    }

    /** @return array{answered: int, correct: int} */
    private function quizStats(int $userId, Carbon $from, Carbon $to, ?int $weekId = null): array
    {
        $query = DB::table('quiz_sessions')
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$from, $to]);

        $sessionIds = $this->applyPauseFilters($query, $from, $to, $weekId)->pluck('id');
        if ($sessionIds->isEmpty()) {
            return ['answered' => 0, 'correct' => 0];
        }
        $answered = (int) DB::table('quiz_answers')
            ->whereIn('quiz_session_id', $sessionIds)
            ->count();
        $correct = (int) DB::table('quiz_answers')
            ->whereIn('quiz_session_id', $sessionIds)
            ->where('is_correct', true)
            ->count();

        return ['answered' => $answered, 'correct' => $correct];
    }

    private function quizSessionCount(int $userId, Carbon $from, Carbon $to, ?int $weekId = null): int
    {
        $query = DB::table('quiz_sessions')
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$from, $to]);

        return (int) $this->applyPauseFilters($query, $from, $to, $weekId)->count();
    }

    /** @return array{general: int, specific: int, general_correct: int, specific_correct: int} */
    private function forecastStats(int $userId, Carbon $from, Carbon $to, ?int $weekId = null): array
    {
        $generalQ = DB::table('forecasts')
            ->where('user_id', $userId)
            ->where('type', 'general')
            ->whereBetween('created_at', [$from, $to]);
        $general = (int) $this->applyPauseFilters($generalQ, $from, $to, $weekId)->count();

        $specificQ = DB::table('forecasts')
            ->where('user_id', $userId)
            ->where('type', 'specific')
            ->whereBetween('created_at', [$from, $to]);
        $specific = (int) $this->applyPauseFilters($specificQ, $from, $to, $weekId)->count();

        $generalCorrectQ = DB::table('forecasts')
            ->where('user_id', $userId)
            ->where('type', 'general')
            ->where('is_correct', true)
            ->whereBetween('created_at', [$from, $to]);
        $generalCorrect = (int) $this->applyPauseFilters($generalCorrectQ, $from, $to, $weekId)->count();

        $specificCorrectQ = DB::table('forecasts')
            ->where('user_id', $userId)
            ->where('type', 'specific')
            ->where('is_correct', true)
            ->whereBetween('created_at', [$from, $to]);
        $specificCorrect = (int) $this->applyPauseFilters($specificCorrectQ, $from, $to, $weekId)->count();

        return [
            'general' => $general,
            'specific' => $specific,
            'general_correct' => $generalCorrect,
            'specific_correct' => $specificCorrect,
        ];
    }

    private function depositPoints(int $userId, Carbon $from, Carbon $to, ?int $weekId = null): float
    {
        $query = DB::table('transactions')
            ->where('user_id', $userId)
            ->where('type', 'deposit')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$from, $to]);

        $sum = (float) $this->applyPauseFilters($query, $from, $to, $weekId)->sum('amount');

        return (float) (floor($sum / 500) * 2);
    }

    private function referralCount(int $userId, Carbon $from, Carbon $to, ?int $weekId = null): int
    {
        $query = User::query()
            ->where('referred_by', $userId)
            ->whereBetween('created_at', [$from, $to]);

        return (int) $this->applyPauseFilters($query, $from, $to, $weekId)->count();
    }

    private function withdrawalPenalty(int $userId, Carbon $from, Carbon $to, ?int $weekId = null): float
    {
        $query = DB::table('transactions')
            ->where('user_id', $userId)
            ->where('type', 'withdrawal')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$from, $to]);

        $n = (int) $this->applyPauseFilters($query, $from, $to, $weekId)->count();

        return -10.0 * $n;
    }

    private function completedDepositCount(int $userId, Carbon $from, Carbon $to, ?int $weekId = null): int
    {
        $query = DB::table('transactions')
            ->where('user_id', $userId)
            ->where('type', 'deposit')
            ->where('status', 'completed')
            ->where('amount', '>=', self::MIN_QUALIFYING_DEPOSIT_AMOUNT)
            ->whereBetween('created_at', [$from, $to]);

        return (int) $this->applyPauseFilters($query, $from, $to, $weekId)->count();
    }

    /**
     * @param  Collection<int, array>  $board
     */
    public function buildBoard(Collection $board, string $sortKey): Collection
    {
        return $board->sortByDesc($sortKey)->values()->map(function ($row, $idx) use ($sortKey) {
            $rank = $idx + 1;
            $row['rank'] = $rank;
            $row['highlight_tournament'] = $row['qualified'] && $rank <= self::TOURNAMENT_CUTOFF;

            return $row;
        });
    }

    public function hydrateUserRows(Collection $scores, ?int $weekId = null): Collection
    {
        [$from, $to] = $this->periodCarbonBounds($weekId);

        return $scores->map(function ($row) use ($from, $to, $weekId) {
            $uid = $row['user_id'];
            $q = $this->qualification($uid, $from, $to, array_merge($row['breakdown'], ['week_id' => $weekId]));
            $user = User::query()->find($uid);

            return array_merge($row, [
                'name' => $user->name ?? 'User #'.$uid,
                'qualified' => $q['qualified'],
                'qualification' => $q,
            ]);
        });
    }

    public function getHistoricalWeeks(): Collection
    {
        return \App\Models\LeaderboardWeek::query()
            ->orderByDesc('start_date')
            ->get();
    }

    public function promptsForUser(
        int $userId,
        int $rankF,
        int $rankW,
        float $scoreF,
        float $scoreW,
        array $qual,
        Collection $fBoard,
        Collection $wBoard,
        int $topRank = 32
    ): array {
        $prompts = [];

        if (!$qual['qualified']) {
            $prompts[] = [
                'type' => 'warning',
                'text' => "You're not yet qualified for the tournament! Complete your missing requirements to secure your spot.",
            ];
        }

        // Point gaps for Top Rank
        $fThreshold = $fBoard->where('rank', $topRank)->first()['frequent'] ?? 0;
        $wThreshold = $wBoard->where('rank', $topRank)->first()['wins'] ?? 0;

        if ($rankF > $topRank && $fThreshold > 0) {
            $gap = round($fThreshold - $scoreF + 0.1, 1);
            if ($gap > 0) {
                $prompts[] = [
                    'type' => 'info',
                    'text' => "You're just $gap pts away from the Top $topRank in Frequent plays!",
                ];
            }
        }

        if ($rankW > $topRank && $wThreshold > 0) {
            $gap = round($wThreshold - $scoreW + 0.1, 1);
            if ($gap > 0) {
                $prompts[] = [
                    'type' => 'info',
                    'text' => "You're just $gap pts away from the Top $topRank in Wins!",
                ];
            }
        }

        return $prompts;
    }
}
