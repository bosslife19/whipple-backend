<?php

namespace App\Http\Services;

use App\Models\LeaderboardPeriod;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LeaderboardCalculationService
{
    public const SKILL_KEYS = ['tap_rush', 'math_clash', 'color_switch', 'defuse_x'];

    /** @var array<string, array{freq: float, win: float}> */
    protected const SKILL_WEIGHTS = [
        'tap_rush' => ['freq' => 1.0, 'win' => 1.5],
        'math_clash' => ['freq' => 2.0, 'win' => 2.5],
        'color_switch' => ['freq' => 3.0, 'win' => 3.5],
        'defuse_x' => ['freq' => 4.0, 'win' => 4.5],
    ];

    public function currentPeriod(): LeaderboardPeriod
    {
        return LeaderboardPeriod::currentOrNew();
    }

    public function periodBounds(?LeaderboardPeriod $period = null): array
    {
        $p = $period ?? $this->currentPeriod();
        $start = Carbon::parse($p->starts_at);
        $end = $p->windowEnd();

        return [$start, $end];
    }

    /**
     * @return Collection<int, array{user_id: int, name: string, score: float, qualified: bool, breakdown: array, counts: array}>
     */
    public function buildLeaderboard(string $type, int $limit = 100, ?LeaderboardPeriod $period = null): Collection
    {
        [$start, $end] = $this->periodBounds($period);
        $stats = $this->aggregateUserStats($start, $end);

        $rows = $stats->map(function (array $s) use ($type): array {
            $score = $type === 'most_wins'
                ? $this->scoreMostWins($s)
                : $this->scoreMostFrequent($s);

            return [
                'user_id' => $s['user_id'],
                'name' => $s['name'],
                'score' => round($score, 4),
                'qualified' => $this->isQualified($s),
                'breakdown' => $s,
                'counts' => $this->qualificationCounts($s),
            ];
        })->sortByDesc('score')->values();

        $ranked = $rows->take($limit)->map(function (array $row, int $idx): array {
            $row['rank'] = $idx + 1;
            $row['tournament_cutline'] = $idx < 32;

            return $row;
        });

        return $ranked;
    }

    public function userStanding(int $userId, string $type, ?LeaderboardPeriod $period = null): ?array
    {
        $board = $this->buildLeaderboard($type, 5000, $period);

        return $board->firstWhere('user_id', $userId);
    }

    /**
     * @return array{prompts: string[], missing: array<string, mixed>, gap_to_cutline: ?float, rank: ?int}
     */
    public function qualificationPrompts(User $user, ?LeaderboardPeriod $period = null): array
    {
        [$start, $end] = $this->periodBounds($period);
        $stats = $this->aggregateUserStats($start, $end);
        $mine = $stats->firstWhere('user_id', $user->id);
        if (! $mine) {
            $mine = $this->emptyStatsRow($user);
            $stats = collect([$mine]);
        }

        $counts = $this->qualificationCounts($mine);
        $prompts = [];
        foreach ($counts as $key => $meta) {
            if ($meta['need'] > 0) {
                $prompts[] = $meta['label'].': need '.$meta['need'].' more (have '.$meta['have'].').';
            }
        }

        $freqBoard = $this->buildLeaderboard('most_frequent', 100, $period);
        $winBoard = $this->buildLeaderboard('most_wins', 100, $period);

        $fr = $freqBoard->firstWhere('user_id', $user->id);
        $wr = $winBoard->firstWhere('user_id', $user->id);

        $gapF = null;
        if ($fr && ($fr['rank'] ?? 999) > 32) {
            $cut = $freqBoard->get(31);
            if ($cut) {
                $gapF = max(0, ($cut['score'] ?? 0) - ($fr['score'] ?? 0));
            }
        }
        $gapW = null;
        if ($wr && ($wr['rank'] ?? 999) > 32) {
            $cut = $winBoard->get(31);
            if ($cut) {
                $gapW = max(0, ($cut['score'] ?? 0) - ($wr['score'] ?? 0));
            }
        }

        return [
            'prompts' => $prompts,
            'missing' => $counts,
            'gap_most_frequent_to_top_32' => $gapF,
            'gap_most_wins_to_top_32' => $gapW,
            'rank_most_frequent' => $fr['rank'] ?? null,
            'rank_most_wins' => $wr['rank'] ?? null,
            'qualified_most_frequent' => (bool) ($fr['qualified'] ?? false),
            'qualified_most_wins' => (bool) ($wr['qualified'] ?? false),
        ];
    }

    protected function emptyStatsRow(User $user): array
    {
        return [
            'user_id' => $user->id,
            'name' => $user->name,
            'skill' => [],
            'quiz_sessions' => 0,
            'quiz_answered' => 0,
            'quiz_correct' => 0,
            'forecast_general' => 0,
            'forecast_specific' => 0,
            'forecast_general_correct' => 0,
            'forecast_specific_correct' => 0,
            'deposit_points' => 0.0,
            'deposit_count' => 0,
            'withdrawal_count' => 0,
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function aggregateUserStats(Carbon $start, Carbon $end): Collection
    {
        $skillRows = DB::table('skill_game_match_players as mp')
            ->join('skill_game_matches as m', 'm.id', '=', 'mp.match_id')
            ->join('skill_games as g', 'g.id', '=', 'm.game_id')
            ->join('users as u', 'u.id', '=', 'mp.user_id')
            ->where('m.status', 'finished')
            ->whereBetween('m.finished_at', [$start, $end])
            ->where('mp.status', 'finished')
            ->groupBy('mp.user_id', 'u.name', 'g.key')
            ->select([
                'mp.user_id',
                'u.name',
                'g.key',
                DB::raw('COUNT(*) as rounds'),
                DB::raw('SUM(CASE WHEN mp.rank = 1 AND mp.score > 0 THEN 1 ELSE 0 END) as wins'),
            ])
            ->get();

        $skill = [];
        foreach ($skillRows as $r) {
            $skill[$r->user_id][$r->key] = [
                'rounds' => (int) $r->rounds,
                'wins' => (int) $r->wins,
            ];
        }

        $quizSessionCounts = DB::table('quiz_sessions')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('user_id')
            ->select(['user_id', DB::raw('COUNT(*) as sessions')])
            ->get()
            ->keyBy('user_id');

        $quizRows = DB::table('quiz_answers as qa')
            ->join('quiz_sessions as qs', 'qs.id', '=', 'qa.quiz_session_id')
            ->whereBetween('qa.created_at', [$start, $end])
            ->groupBy('qs.user_id')
            ->select([
                'qs.user_id',
                DB::raw('COUNT(qa.id) as answered'),
                DB::raw('SUM(CASE WHEN qa.is_correct = 1 THEN 1 ELSE 0 END) as correct'),
            ])
            ->get()
            ->keyBy('user_id');

        $forecastRows = DB::table('forecasts')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('user_id', 'type')
            ->select([
                'user_id',
                'type',
                DB::raw('COUNT(*) as cnt'),
                DB::raw('SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as wins'),
            ])
            ->get();

        $forecast = [];
        foreach ($forecastRows as $r) {
            if (! isset($forecast[$r->user_id])) {
                $forecast[$r->user_id] = [
                    'general' => 0,
                    'specific' => 0,
                    'general_correct' => 0,
                    'specific_correct' => 0,
                ];
            }
            if ($r->type === 'general') {
                $forecast[$r->user_id]['general'] = (int) $r->cnt;
                $forecast[$r->user_id]['general_correct'] = (int) $r->wins;
            } else {
                $forecast[$r->user_id]['specific'] = (int) $r->cnt;
                $forecast[$r->user_id]['specific_correct'] = (int) $r->wins;
            }
        }

        $txRows = DB::table('transactions')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('user_id')
            ->select([
                'user_id',
                DB::raw("SUM(CASE WHEN type = 'deposit' AND status = 'completed' THEN FLOOR(amount / 500) * 2 ELSE 0 END) as deposit_points"),
                DB::raw("COUNT(CASE WHEN type = 'deposit' AND status = 'completed' THEN 1 END) as deposit_count"),
                DB::raw("COUNT(CASE WHEN type = 'withdrawal' AND status = 'completed' THEN 1 END) as withdrawal_count"),
            ])
            ->get()
            ->keyBy('user_id');

        $userIds = collect(array_keys($skill))
            ->merge($quizRows->keys())
            ->merge($quizSessionCounts->keys())
            ->merge(array_keys($forecast))
            ->merge($txRows->keys())
            ->unique()
            ->values();

        $names = User::whereIn('id', $userIds)->pluck('name', 'id');

        return $userIds->map(function ($uid) use ($skill, $quizRows, $quizSessionCounts, $forecast, $txRows, $names): array {
            $q = $quizRows[$uid] ?? null;
            $qs = $quizSessionCounts[$uid] ?? null;
            $t = $txRows[$uid] ?? null;
            $f = $forecast[$uid] ?? [
                'general' => 0,
                'specific' => 0,
                'general_correct' => 0,
                'specific_correct' => 0,
            ];

            return [
                'user_id' => (int) $uid,
                'name' => $names[$uid] ?? 'User #'.$uid,
                'skill' => $skill[$uid] ?? [],
                'quiz_sessions' => $qs ? (int) $qs->sessions : 0,
                'quiz_answered' => $q ? (int) $q->answered : 0,
                'quiz_correct' => $q ? (int) $q->correct : 0,
                'forecast_general' => $f['general'],
                'forecast_specific' => $f['specific'],
                'forecast_general_correct' => $f['general_correct'],
                'forecast_specific_correct' => $f['specific_correct'],
                'deposit_points' => $t ? (float) $t->deposit_points : 0.0,
                'deposit_count' => $t ? (int) $t->deposit_count : 0,
                'withdrawal_count' => $t ? (int) $t->withdrawal_count : 0,
            ];
        });
    }

    protected function scoreMostFrequent(array $s): float
    {
        $score = 0.0;
        foreach (self::SKILL_KEYS as $key) {
            $rounds = (int) ($s['skill'][$key]['rounds'] ?? 0);
            $w = self::SKILL_WEIGHTS[$key]['freq'] ?? 1;
            $score += $rounds * $w;
        }
        $score += (int) $s['quiz_answered'] * 1.0;
        $score += (int) $s['forecast_general'] * 1.0;
        $score += (int) $s['forecast_specific'] * 1.0;
        $score += (float) $s['deposit_points'];
        $score -= (int) $s['withdrawal_count'] * 10;

        return $score;
    }

    protected function scoreMostWins(array $s): float
    {
        $score = 0.0;
        foreach (self::SKILL_KEYS as $key) {
            $wins = (int) ($s['skill'][$key]['wins'] ?? 0);
            $w = self::SKILL_WEIGHTS[$key]['win'] ?? 1;
            $score += $wins * $w;
        }
        $score += (int) $s['quiz_correct'] * 1.5;
        $score += (int) $s['forecast_general_correct'] * 1.5;
        $score += (int) $s['forecast_specific_correct'] * 1.5;
        $score -= (int) $s['withdrawal_count'] * 10;

        return $score;
    }

    protected function isQualified(array $s): bool
    {
        foreach (self::SKILL_KEYS as $key) {
            if ((int) ($s['skill'][$key]['rounds'] ?? 0) < 3) {
                return false;
            }
        }
        if ((int) $s['quiz_sessions'] < 3) {
            return false;
        }
        if ((int) $s['forecast_general'] < 3 || (int) $s['forecast_specific'] < 3) {
            return false;
        }
        if ((int) $s['deposit_count'] < 3) {
            return false;
        }

        return true;
    }

    /**
     * @return array<string, array{label: string, have: int, need: int}>
     */
    protected function qualificationCounts(array $s): array
    {
        $out = [];
        foreach (self::SKILL_KEYS as $key) {
            $have = (int) ($s['skill'][$key]['rounds'] ?? 0);
            $out['skill_'.$key] = [
                'label' => str_replace('_', ' ', ucfirst($key)),
                'have' => $have,
                'need' => max(0, 3 - $have),
            ];
        }
        $qs = (int) $s['quiz_sessions'];
        $out['quiz_sessions'] = [
            'label' => 'Quiz (sessions played)',
            'have' => $qs,
            'need' => max(0, 3 - $qs),
        ];
        $fg = (int) $s['forecast_general'];
        $fs = (int) $s['forecast_specific'];
        $out['forecast_general'] = [
            'label' => 'Fun Forecast (general)',
            'have' => $fg,
            'need' => max(0, 3 - $fg),
        ];
        $out['forecast_specific'] = [
            'label' => 'Fun Forecast (specific)',
            'have' => $fs,
            'need' => max(0, 3 - $fs),
        ];
        $dc = (int) $s['deposit_count'];
        $out['deposits'] = [
            'label' => 'Successful deposits',
            'have' => $dc,
            'need' => max(0, 3 - $dc),
        ];

        return $out;
    }

    public function resetWeeklyPeriod(?string $label = null): LeaderboardPeriod
    {
        DB::transaction(function (): void {
            LeaderboardPeriod::query()->where('is_current', true)->update(['is_current' => false]);
        });

        return LeaderboardPeriod::query()->create([
            'label' => $label ?? 'Week '.Carbon::now()->format('Y-m-d H:i'),
            'starts_at' => Carbon::now(),
            'ends_at' => null,
            'is_current' => true,
        ]);
    }
}
