<?php

namespace App\Http\Controllers;

use App\Models\WhippleTournamentLobby;
use App\Models\SkillGameMatch;
use App\Models\SkillGame;
use App\Services\TournamentService;
use Illuminate\Http\Request;

class TournamentApiController extends Controller
{
    public function __construct(
        protected TournamentService $tournaments
    ) {}

    public function overview(Request $request)
    {
        $upcoming = \App\Models\WhippleTournament::query()
            ->whereIn('status', ['draft', 'active'])
            ->orderBy('start_at', 'asc')
            ->get();

        $history = \App\Models\WhippleTournament::query()
            ->where('status', 'completed')
            ->orderBy('start_at', 'desc')
            ->take(10)
            ->get();

        $userId = $request->user()->id;

        return response()->json([
            'status' => true,
            'upcoming' => $upcoming->map(fn($t) => $this->serializeTournament($t, $userId)),
            'history' => $history->map(fn($t) => $this->serializeTournament($t, $userId)),
        ]);
    }

    public function show(Request $request, int $id)
    {
        $t = \App\Models\WhippleTournament::query()->findOrFail($id);

        return response()->json([
            'status' => true,
            'tournament' => $this->serializeTournament($t, $request->user()->id),
        ]);
    }

    public function lobbyState(Request $request, int $lobbyId)
    {
        $lobby = WhippleTournamentLobby::query()->with('tournament')->findOrFail($lobbyId);
        $this->authorizePlayer($lobby->tournament, $request->user()->id);
        $lobby = $this->tournaments->syncLobbyState($lobby);

        return response()->json([
            'status' => true,
            'lobby' => $this->serializeLobby($lobby),
        ]);
    }

    public function ackScreenShare(Request $request, int $id)
    {
        $t = \App\Models\WhippleTournament::query()->findOrFail($id);
        $player = $t->players()->where('user_id', $request->user()->id)->firstOrFail();
        $player->update(['screen_share_ack' => true]);

        return response()->json(['status' => true]);
    }

    public function submitLobbyScore(Request $request, int $lobbyId)
    {
        $data = $request->validate([
            'score' => 'required|numeric',
            'meta' => 'nullable|array',
        ]);
        $lobby = WhippleTournamentLobby::query()->with('tournament')->findOrFail($lobbyId);
        $this->authorizePlayer($lobby->tournament, $request->user()->id);
        if ($lobby->status === 'ended') {
            return response()->json(['status' => false, 'message' => 'Lobby ended'], 422);
        }
        if ($lobby->status !== 'live') {
            return response()->json([
                'status' => false,
                'message' => 'Scores accepted only while the lobby is live (after countdown).',
            ], 422);
        }

        $player = $lobby->tournament->players()
            ->where('user_id', $request->user()->id)
            ->where('eliminated', false)
            ->firstOrFail();

        if (! $player->screen_share_ack) {
            return response()->json([
                'status' => false,
                'message' => 'Acknowledge screen sharing in the app before competing.',
                'require_screen_share' => true,
            ], 422);
        }

        $this->tournaments->updateLobbyScore($lobby, $request->user()->id, (float) $data['score'], $data['meta'] ?? null);
        $this->tournaments->rankLobbyScores($lobby);

        return response()->json(['status' => true, 'lobby' => $this->serializeLobby($lobby->refresh())]);
    }

    protected function authorizePlayer(\App\Models\WhippleTournament $t, int $userId): void
    {
        $t->players()->where('user_id', $userId)->firstOrFail();
    }

    protected function serializeTournament(\App\Models\WhippleTournament $t, int $viewerId): array
    {
        $t->load(['players.user', 'lobbies.scores.user', 'streamSlots.user', 'commentary.author']);

        $lastLobby = $t->lobbies
            ->sortByDesc(fn ($lobby) => $lobby->started_at?->getTimestamp() ?? $lobby->id)
            ->first();

        $players = $t->players->map(function ($p) use ($lastLobby) {
            $latestScore = 0;
            if ($lastLobby) {
                $scoreRow = $lastLobby->scores->firstWhere('user_id', $p->user_id);
                if ($scoreRow) {
                    $latestScore = (float) $scoreRow->score;
                }
            }

            return [
                'user_id' => $p->user_id,
                'name' => $p->user->name ?? '',
                'source' => $p->source,
                'import_rank' => $p->import_rank,
                'eliminated' => $p->eliminated,
                'screen_share_ack' => $p->screen_share_ack,
                'total_score' => $latestScore,
            ];
        });

        $players = $players->sort(function ($a, $b) {
            if ($a['eliminated'] !== $b['eliminated']) {
                return $a['eliminated'] ? 1 : -1;
            }
            if ($a['total_score'] !== $b['total_score']) {
                return $b['total_score'] <=> $a['total_score'];
            }
            return $a['name'] <=> $b['name'];
        })->values();

        $currentRank = 1;
        $players = $players->map(function ($p) use (&$currentRank) {
            $p['current_rank'] = $currentRank++;
            return $p;
        });

        $lobbies = $t->lobbies->map(fn ($l) => $this->serializeLobby($l));

        $me = $t->players->firstWhere('user_id', $viewerId);

        $top = $t->players->where('eliminated', false)->sortBy('import_rank')->take(8)->values()->map(function ($p) {
            return [
                'user_id' => $p->user_id,
                'name' => $p->user->name ?? '',
                'import_rank' => $p->import_rank,
            ];
        });

        // Top 3 Winners (if completed)
        $winners = [];
        if ($t->status === 'completed') {
            $winners = $players->take(3)->map(fn($p) => [
                'name' => $p['name'] ?: 'User #'.$p['user_id'],
                'score' => (float) $p['total_score'],
                'rank' => $p['current_rank'],
            ]);
        }

        // Leaderboard Top 3 (for upcoming)
        $leaderboardTop3 = [];
        if ($t->status !== 'completed') {
            $ls = app(\App\Services\LeaderboardService::class);
            $raw = $ls->computeScoresForAllUsers();
            $hydrated = $ls->hydrateUserRows($raw);
            $leaderboardTop3 = $ls->buildBoard($hydrated, 'wins')->take(3)->map(fn($r) => [
                'name' => $r['name'],
                'score' => (float) $r['wins'],
            ]);
        }

        // Active Match for the tournament
        $activeMatch = SkillGameMatch::where('tournament_id', $t->id)
            ->where('status', 'waiting')
            ->where('game_type', 'tournament')
            ->latest()
            ->first();

        $activeLobby = $t->lobbies->whereIn('status', ['pending', 'countdown', 'live'])->last();
        $activeLobbyData = $activeLobby ? $this->serializeLobby($activeLobby) : null;

        // Last ended lobby for ranking/elimination display
        $lastLobby = $t->lobbies()->where('status', 'ended')->latest('ended_at')->first();
        $lastLobbyData = null;
        if ($lastLobby) {
            $lastLobbyData = $this->serializeLobby($lastLobby);
        }

        return [
            'id' => $t->id,
            'title' => $t->title,
            'status' => $t->status,
            'start_at' => $t->start_at?->toIso8601String(),
            'players' => $players,
            'active_players' => $t->players->where('eliminated', false)->count(),
            'eliminated_players' => $t->players->where('eliminated', true)->count(),
            'total_players' => $t->players->count(),
            'top_players_preview' => $top,
            'lobbies' => $lobbies,
            'active_lobby' => $activeLobbyData,
            'last_lobby' => $lastLobbyData,
            'active_match' => $activeMatch ? [
                'id' => $activeMatch->id,
                'game_key' => $activeMatch->game->key ?? '',
                'game_name' => $activeMatch->game->name ?? '',
                'max_players' => $activeMatch->max_players,
                'started_at' => $activeMatch->started_at?->toIso8601String(),
            ] : null,
            'stream_slots' => $t->streamSlots->map(fn ($s) => [
                'slot' => $s->slot,
                'user_id' => $s->user_id,
                'name' => $s->user->name ?? '',
            ]),
            'commentary' => $t->commentary->take(100)->map(fn ($c) => [
                'id' => $c->id,
                'body' => $c->body,
                'is_key_moment' => $c->is_key_moment,
                'created_at' => $c->created_at->toIso8601String(),
            ]),
            'me' => $me ? [
                'eliminated' => $me->eliminated,
                'screen_share_ack' => $me->screen_share_ack,
            ] : null,
            'is_invited' => $me !== null,
            'winners_top_3' => $winners,
            'leaderboard_top_3' => $leaderboardTop3,
        ];
    }

    protected function serializeLobby(WhippleTournamentLobby $lobby): array
    {
        $lobby->loadMissing('scores.user');
        $scores = $lobby->scores->sortByDesc('score')->values()->map(function ($s, $idx) {
            return [
                'rank' => $s->rank ?? ($idx + 1),
                'user_id' => $s->user_id,
                'name' => $s->user->name ?? '',
                'score' => (float) $s->score,
            ];
        });

        return [
            'id' => $lobby->id,
            'game_key' => $lobby->game_key,
            'label' => $lobby->label,
            'status' => $lobby->status,
            'countdown_seconds' => $lobby->countdown_seconds,
            'countdown_started_at' => $lobby->countdown_started_at?->toIso8601String(),
            'started_at' => $lobby->started_at?->toIso8601String(),
            'ended_at' => $lobby->ended_at?->toIso8601String(),
            'leaderboard' => $scores,
        ];
    }
}
