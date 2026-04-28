<?php

namespace App\Services;

use App\Models\User;
use App\Models\WhippleTournament;
use App\Models\WhippleTournamentCommentary;
use App\Models\WhippleTournamentLobby;
use App\Models\WhippleTournamentLobbyScore;
use App\Models\WhippleTournamentPlayer;
use App\Models\WhippleTournamentStreamSlot;
use App\Models\SkillGame;
use App\Models\SkillGameMatch;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class TournamentService
{
    public function __construct(
        protected LeaderboardService $leaderboard
    ) {}

    public function activeTournament(): ?WhippleTournament
    {
        return WhippleTournament::query()
            ->whereIn('status', ['draft', 'active'])
            ->latest()
            ->first();
    }

    public function importFromLeaderboard(WhippleTournament $tournament, string $source, int $limit): int
    {
        if (! in_array($source, ['frequent', 'wins'], true)) {
            throw new \InvalidArgumentException('Invalid source');
        }

        $raw = $this->leaderboard->computeScoresForAllUsers();
        $hydrated = $this->leaderboard->hydrateUserRows($raw);
        $key = $source === 'frequent' ? 'frequent' : 'wins';
        $board = $this->leaderboard->buildBoard($hydrated, $key);
        $qualifiedSlice = $board->take($limit)->values();

        $added = 0;
        $rank = 1;
        foreach ($qualifiedSlice as $row) {
            WhippleTournamentPlayer::query()->updateOrCreate(
                [
                    'tournament_id' => $tournament->id,
                    'user_id' => $row['user_id'],
                ],
                [
                    'source' => $source,
                    'import_rank' => $rank,
                    'eliminated' => false,
                    'eliminated_at' => null,
                ]
            );
            $added++;
            $rank++;
        }

        return $added;
    }

    public function addManualPlayer(WhippleTournament $tournament, string $emailOrUsername): WhippleTournamentPlayer
    {
        $user = User::query()->where('email', $emailOrUsername)->first()
            ?? User::query()->where('name', $emailOrUsername)->first();

        if (! $user) {
            throw new ModelNotFoundException('No user found with that email or name.');
        }

        return WhippleTournamentPlayer::query()->updateOrCreate(
            [
                'tournament_id' => $tournament->id,
                'user_id' => $user->id,
            ],
            [
                'source' => 'manual',
                'import_rank' => null,
                'eliminated' => false,
            ]
        );
    }

    public function addManualPlayerById(WhippleTournament $tournament, int $userId): WhippleTournamentPlayer
    {
        return WhippleTournamentPlayer::query()->updateOrCreate(
            [
                'tournament_id' => $tournament->id,
                'user_id' => $userId,
            ],
            [
                'source' => 'manual',
                'import_rank' => null,
                'eliminated' => false,
            ]
        );
    }

    public function removePlayer(WhippleTournament $tournament, int $userId): void
    {
        WhippleTournamentPlayer::query()
            ->where('tournament_id', $tournament->id)
            ->where('user_id', $userId)
            ->delete();
        WhippleTournamentLobbyScore::query()
            ->whereIn('lobby_id', $tournament->lobbies()->pluck('id'))
            ->where('user_id', $userId)
            ->delete();
        WhippleTournamentStreamSlot::query()
            ->where('tournament_id', $tournament->id)
            ->where('user_id', $userId)
            ->delete();
    }

    public function createLobby(WhippleTournament $tournament, string $gameKey, ?string $label = null): WhippleTournamentLobby
    {
        $lobby = $tournament->lobbies()->create([
            'game_key' => $gameKey,
            'label' => $label,
            'status' => 'pending',
            'countdown_seconds' => 20,
        ]);

        $game = SkillGame::where('key', $gameKey)->first();
        if ($game) {
            $activePlayerCount = $tournament->players()->where('eliminated', false)->count();
            
            SkillGameMatch::create([
                'game_id' => $game->id,
                'status' => 'waiting',
                'max_players' => max(1, $activePlayerCount),
                'match_time_window' => 30,
                'countdown' => 5,
                'started_at' => Carbon::now()->addSeconds(30),
                'game_type' => 'tournament',
                'tournament_id' => $tournament->id,
                'lobby_id' => $lobby->id,
                'meta' => null,
            ]);
        }

        return $lobby;
    }

    /** Attach all non-eliminated players (or subset by ids) */
    public function addPlayersToLobby(WhippleTournamentLobby $lobby, ?array $userIds = null): int
    {
        $q = $lobby->tournament->players()->where('eliminated', false);
        if ($userIds) {
            $q->whereIn('user_id', $userIds);
        }
        $n = 0;
        foreach ($q->get() as $tp) {
            WhippleTournamentLobbyScore::query()->firstOrCreate(
                ['lobby_id' => $lobby->id, 'user_id' => $tp->user_id],
                ['score' => 0, 'rank' => null]
            );
            $n++;
        }

        return $n;
    }

    public function startCountdown(WhippleTournamentLobby $lobby): WhippleTournamentLobby
    {
        $lobby->update([
            'status' => 'countdown',
            'countdown_started_at' => Carbon::now(),
        ]);

        return $lobby->refresh();
    }

    /**
     * Auto-advance countdown -> live when elapsed; caller should poll.
     */
    public function syncLobbyState(WhippleTournamentLobby $lobby): WhippleTournamentLobby
    {
        if ($lobby->status !== 'countdown' || ! $lobby->countdown_started_at) {
            return $lobby;
        }
        $sec = (int) $lobby->countdown_seconds;
        $end = $lobby->countdown_started_at->copy()->addSeconds($sec);
        if (Carbon::now()->greaterThanOrEqualTo($end)) {
            $lobby->update([
                'status' => 'live',
                'started_at' => $lobby->started_at ?? $end,
            ]);
        }

        return $lobby->refresh();
    }

    public function endLobby(WhippleTournamentLobby $lobby): WhippleTournamentLobby
    {
        $this->rankLobbyScores($lobby);
        $lobby->update([
            'status' => 'ended',
            'ended_at' => Carbon::now(),
        ]);

        return $lobby->refresh();
    }

    public function rankLobbyScores(WhippleTournamentLobby $lobby): void
    {
        $scores = WhippleTournamentLobbyScore::query()
            ->where('lobby_id', $lobby->id)
            ->orderByDesc('score')
            ->get();
        $r = 1;
        foreach ($scores as $s) {
            $s->update(['rank' => $r++]);
        }
    }

    public function eliminateBottom(WhippleTournament $tournament, int $count): int
    {
        if ($count <= 0) {
            return 0;
        }

        $activePlayers = $tournament->players()->where('eliminated', false)->get();
        if ($activePlayers->isEmpty()) {
            return 0;
        }

        $lobbies = $tournament->lobbies()->with('scores')->get();

        $playerScores = $activePlayers->map(function ($p) use ($lobbies) {
            $totalScore = 0;
            foreach ($lobbies as $l) {
                $scoreRow = $l->scores->firstWhere('user_id', $p->user_id);
                if ($scoreRow) {
                    $totalScore += (float) $scoreRow->score;
                }
            }
            return (object) [
                'user_id' => $p->user_id,
                'total_score' => $totalScore,
            ];
        });

        $bottom = $playerScores->sortBy('total_score')->take($count);

        $n = 0;
        foreach ($bottom as $row) {
            WhippleTournamentPlayer::query()
                ->where('tournament_id', $tournament->id)
                ->where('user_id', $row->user_id)
                ->update(['eliminated' => true, 'eliminated_at' => Carbon::now()]);
            $n++;
        }

        return $n;
    }

    public function resetTournament(WhippleTournament $tournament): void
    {
        DB::transaction(function () use ($tournament) {
            WhippleTournamentCommentary::query()->where('tournament_id', $tournament->id)->delete();
            WhippleTournamentStreamSlot::query()->where('tournament_id', $tournament->id)->delete();
            foreach ($tournament->lobbies as $l) {
                $l->scores()->delete();
                $l->delete();
            }
            WhippleTournamentPlayer::query()->where('tournament_id', $tournament->id)->delete();
            $tournament->update([
                'status' => 'draft',
                'title' => 'Whipple Tournament',
            ]);
        });
    }

    public function postCommentary(WhippleTournament $tournament, string $body, bool $keyMoment, ?int $adminUserId): WhippleTournamentCommentary
    {
        return WhippleTournamentCommentary::query()->create([
            'tournament_id' => $tournament->id,
            'user_id' => $adminUserId,
            'body' => $body,
            'is_key_moment' => $keyMoment,
        ]);
    }

    public function setStreamSlot(WhippleTournament $tournament, int $slot, int $userId): void
    {
        if (! in_array($slot, [1, 2], true)) {
            throw new \InvalidArgumentException('Slot must be 1 or 2');
        }
        WhippleTournamentPlayer::query()
            ->where('tournament_id', $tournament->id)
            ->where('user_id', $userId)
            ->firstOrFail();

        WhippleTournamentStreamSlot::query()->updateOrCreate(
            ['tournament_id' => $tournament->id, 'slot' => $slot],
            ['user_id' => $userId]
        );
    }

    public function updateLobbyScore(WhippleTournamentLobby $lobby, int $userId, float $score, ?array $meta = null): WhippleTournamentLobbyScore
    {
        $row = WhippleTournamentLobbyScore::query()->firstOrCreate(
            ['lobby_id' => $lobby->id, 'user_id' => $userId],
            ['score' => 0]
        );
        $row->update([
            'score' => $score,
            'meta' => $meta ?? $row->meta,
        ]);

        return $row->refresh();
    }
}
