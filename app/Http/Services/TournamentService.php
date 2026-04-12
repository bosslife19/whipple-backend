<?php

namespace App\Http\Services;

use App\Models\Tournament;
use App\Models\TournamentCommentary;
use App\Models\TournamentParticipant;
use App\Models\TournamentRound;
use App\Models\TournamentRoundScore;
use App\Models\TournamentScreenShare;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TournamentService
{
    public function __construct(
        protected LeaderboardCalculationService $leaderboards
    ) {}

    public function importFromLeaderboards(
        Tournament $tournament,
        int $fromFrequent,
        int $fromWins,
        int $adminUserId
    ): void {
        if ($fromFrequent < 0 || $fromWins < 0) {
            throw ValidationException::withMessages(['import' => 'Counts must be non-negative.']);
        }

        $period = $this->leaderboards->currentPeriod();

        $freq = $fromFrequent > 0
            ? $this->leaderboards->buildLeaderboard('most_frequent', $fromFrequent, $period)->take($fromFrequent)
            : collect();
        $wins = $fromWins > 0
            ? $this->leaderboards->buildLeaderboard('most_wins', $fromWins, $period)->take($fromWins)
            : collect();

        DB::transaction(function () use ($tournament, $freq, $wins, $adminUserId): void {
            foreach ($freq as $row) {
                TournamentParticipant::query()->updateOrCreate(
                    [
                        'tournament_id' => $tournament->id,
                        'user_id' => $row['user_id'],
                    ],
                    [
                        'source' => 'most_frequent',
                        'import_rank' => $row['rank'],
                        'eliminated_at' => null,
                    ]
                );
            }
            foreach ($wins as $row) {
                TournamentParticipant::query()->updateOrCreate(
                    [
                        'tournament_id' => $tournament->id,
                        'user_id' => $row['user_id'],
                    ],
                    [
                        'source' => 'most_wins',
                        'import_rank' => $row['rank'],
                        'eliminated_at' => null,
                    ]
                );
            }
            $tournament->update([
                'status' => 'active',
                'created_by_user_id' => $adminUserId,
            ]);
        });
    }

    public function addParticipantByIdentifier(Tournament $tournament, string $emailOrUsername): TournamentParticipant
    {
        $user = User::query()
            ->where('email', $emailOrUsername)
            ->orWhere('name', $emailOrUsername)
            ->orWhere('phone', $emailOrUsername)
            ->first();

        if (! $user) {
            throw ValidationException::withMessages(['user' => 'No user found for that email, name, or phone.']);
        }

        return TournamentParticipant::query()->firstOrCreate(
            [
                'tournament_id' => $tournament->id,
                'user_id' => $user->id,
            ],
            [
                'source' => 'manual',
                'import_rank' => null,
            ]
        );
    }

    public function removeParticipant(Tournament $tournament, int $userId): void
    {
        TournamentParticipant::query()
            ->where('tournament_id', $tournament->id)
            ->where('user_id', $userId)
            ->delete();
    }

    public function startRoundCountdown(TournamentRound $round): void
    {
        $round->update([
            'status' => 'countdown',
            'countdown_ends_at' => Carbon::now()->addSeconds($round->countdown_seconds ?? 20),
        ]);
    }

    public function createRound(Tournament $tournament, string $gameType, int $roundNumber = 1): TournamentRound
    {
        return DB::transaction(function () use ($tournament, $gameType, $roundNumber): TournamentRound {
            $round = TournamentRound::query()->create([
                'tournament_id' => $tournament->id,
                'round_number' => $roundNumber,
                'game_type' => $gameType,
                'status' => 'pending',
                'countdown_seconds' => 20,
            ]);

            $activeIds = $tournament->participants()
                ->whereNull('eliminated_at')
                ->pluck('user_id');

            foreach ($activeIds as $userId) {
                TournamentRoundScore::query()->firstOrCreate(
                    [
                        'tournament_round_id' => $round->id,
                        'user_id' => $userId,
                    ],
                    [
                        'score' => 0,
                    ]
                );
            }

            return $round;
        });
    }

    /**
     * After countdown ends (called by cron or client poll completing countdown).
     */
    public function markRoundStarted(TournamentRound $round): void
    {
        $round->update([
            'status' => 'started',
            'started_at' => Carbon::now(),
        ]);
    }

    public function markRoundEnded(TournamentRound $round): void
    {
        $round->update([
            'status' => 'ended',
            'ended_at' => Carbon::now(),
        ]);
        $tournament = $round->tournament;
        $tournament->update(['status' => 'active']);
    }

    public function upsertRoundScore(TournamentRound $round, int $userId, int $score, ?string $time = null, array $meta = []): TournamentRoundScore
    {
        $rec = TournamentRoundScore::query()->updateOrCreate(
            [
                'tournament_round_id' => $round->id,
                'user_id' => $userId,
            ],
            [
                'score' => $score,
                'time' => $time,
                'finished_at' => Carbon::now(),
                'meta' => $meta,
            ]
        );

        $this->rerankRound($round);

        return $rec;
    }

    public function rerankRound(TournamentRound $round): void
    {
        $scores = TournamentRoundScore::query()
            ->where('tournament_round_id', $round->id)
            ->orderByDesc('score')
            ->orderBy('time')
            ->get();

        $rank = 1;
        foreach ($scores as $s) {
            $s->update(['rank' => $rank++]);
        }
    }

    public function eliminateBottom(Tournament $tournament, int $count): int
    {
        if ($count <= 0) {
            return 0;
        }

        $round = $tournament->rounds()->orderByDesc('id')->first();
        if (! $round) {
            throw ValidationException::withMessages(['round' => 'No tournament round to rank eliminations against.']);
        }

        $bottom = TournamentRoundScore::query()
            ->where('tournament_round_id', $round->id)
            ->orderBy('score')
            ->orderByDesc('time')
            ->limit($count)
            ->get();

        $n = 0;
        foreach ($bottom as $row) {
            TournamentParticipant::query()
                ->where('tournament_id', $tournament->id)
                ->where('user_id', $row->user_id)
                ->update(['eliminated_at' => Carbon::now()]);
            $n++;
        }

        return $n;
    }

    public function resetTournament(Tournament $tournament): void
    {
        DB::transaction(function () use ($tournament): void {
            $tournament->rounds()->each(function (TournamentRound $r): void {
                $r->scores()->delete();
            });
            $tournament->rounds()->delete();
            $tournament->participants()->delete();
            $tournament->commentaries()->delete();
            $tournament->screenShares()->delete();
            $tournament->update([
                'status' => 'draft',
                'title' => $tournament->title,
            ]);
        });
    }

    public function addCommentary(Tournament $tournament, int $adminUserId, string $body, bool $keyMoment = false): TournamentCommentary
    {
        return TournamentCommentary::query()->create([
            'tournament_id' => $tournament->id,
            'admin_user_id' => $adminUserId,
            'body' => $body,
            'is_key_moment' => $keyMoment,
        ]);
    }

    public function setScreenShareSlot(Tournament $tournament, int $slot, int $userId): TournamentScreenShare
    {
        if ($slot < 1 || $slot > 2) {
            throw ValidationException::withMessages(['slot' => 'Slot must be 1 or 2.']);
        }

        $p = TournamentParticipant::query()
            ->where('tournament_id', $tournament->id)
            ->where('user_id', $userId)
            ->first();
        if (! $p) {
            throw ValidationException::withMessages(['user' => 'User is not in this tournament.']);
        }

        return TournamentScreenShare::query()->updateOrCreate(
            [
                'tournament_id' => $tournament->id,
                'slot' => $slot,
            ],
            [
                'user_id' => $userId,
                'is_active' => true,
            ]
        );
    }

    public function tournamentState(Tournament $tournament): array
    {
        $parts = $tournament->participants()->with('user:id,name,email')->get();
        $active = $parts->filter(fn ($p) => $p->isActive())->values();
        $eliminated = $parts->filter(fn ($p) => ! $p->isActive())->values();

        $round = $tournament->rounds()->orderByDesc('id')->first();
        $roundPayload = null;
        if ($round) {
            $roundPayload = [
                'id' => $round->id,
                'game_type' => $round->game_type,
                'status' => $round->status,
                'countdown_seconds' => $round->countdown_seconds,
                'countdown_ends_at' => $round->countdown_ends_at,
                'started_at' => $round->started_at,
                'ended_at' => $round->ended_at,
                'leaderboard' => $round->scores()->with('user:id,name')->orderBy('rank')->get()->map(fn ($s) => [
                    'user_id' => $s->user_id,
                    'name' => $s->user->name,
                    'score' => $s->score,
                    'rank' => $s->rank,
                    'time' => $s->time,
                ]),
            ];
        }

        $top = $round?->scores()->with('user:id,name')->orderBy('rank')->limit(10)->get() ?? collect();

        return [
            'tournament' => [
                'id' => $tournament->id,
                'uuid' => $tournament->uuid,
                'title' => $tournament->title,
                'status' => $tournament->status,
            ],
            'counts' => [
                'total' => $parts->count(),
                'active' => $active->count(),
                'eliminated' => $eliminated->count(),
            ],
            'active_players' => $active,
            'eliminated_players' => $eliminated,
            'current_round' => $roundPayload,
            'top_players' => $top,
            'screen_shares' => $tournament->screenShares()->with('user:id,name')->get(),
            'commentary' => $tournament->commentaries()->orderByDesc('id')->limit(50)->get(),
        ];
    }

    public function ensureParticipant(Tournament $tournament, int $userId): void
    {
        $exists = TournamentParticipant::query()
            ->where('tournament_id', $tournament->id)
            ->where('user_id', $userId)
            ->exists();
        if (! $exists) {
            abort(403, 'Not a participant in this tournament.');
        }
    }
}
