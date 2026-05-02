<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Services\MatchmakingService;
use App\Models\SkillGameMatch;
use App\Models\User;
use App\Models\WhippleTournament;
use App\Models\WhippleTournamentPlayer;
use App\Services\TournamentService;
use Illuminate\Http\Request;

class TournamentAdminController extends Controller
{
    public function __construct(
        protected TournamentService $tournaments,
        protected MatchmakingService $matchService,
    ) {}

    public function index(Request $request)
    {
        $query = WhippleTournament::query();

        if ($request->has('id') && $request->id) {
            $tournament = $query->find($request->id);
        } else {
            $tournament = $query->whereIn('status', ['draft', 'active'])->latest()->first();
        }

        $rankedPlayers = collect();
        if ($tournament) {
            $tournament->load(['players.user', 'lobbies.scores.user', 'streamSlots.user', 'commentary.author']);

            $lobbies = $tournament->lobbies;
            $rankedPlayers = $tournament->players->map(function ($p) use ($lobbies) {
                $totalScore = 0;
                foreach ($lobbies as $l) {
                    $scoreRow = $l->scores->firstWhere('user_id', $p->user_id);
                    if ($scoreRow) {
                        $totalScore += (float) $scoreRow->score;
                    }
                }
                $p->total_score = $totalScore;
                return $p;
            })->sort(function ($a, $b) {
                if ($a->eliminated !== $b->eliminated) {
                    return $a->eliminated ? 1 : -1;
                }
                return $b->total_score <=> $a->total_score;
            })->values();

            $rank = 1;
            foreach ($rankedPlayers as $p) {
                $p->current_rank = $rank++;
            }
        }

        $allTournaments = WhippleTournament::latest()->get();

        $users = \App\Models\User::query()->orderBy('name')->get(['id', 'name', 'email']);
        $existingPlayerIds = $tournament ? $tournament->players->pluck('user_id')->toArray() : [];

        return view('admin.tournament', [
            'title' => 'Tournament',
            'tournament' => $tournament,
            'rankedPlayers' => $rankedPlayers,
            'allTournaments' => $allTournaments,
            'users' => $users,
            'existingPlayerIds' => $existingPlayerIds,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'nullable|string|max:255',
            'start_at' => 'required|date',
        ]);
        $tournament = WhippleTournament::query()->create([
            'title' => $data['title'] ?? 'Whipple Tournament',
            'start_at' => $data['start_at'],
            'status' => 'draft',
        ]);

        return redirect()->route('admin.tournament')->with('status', 'Tournament created. Import players or add manually.');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'tournament_id' => 'required|exists:whipple_tournaments,id',
            'title' => 'required|string|max:255',
            'start_at' => 'required|date',
            'status' => 'required|in:draft,active,completed',
        ]);
        $t = WhippleTournament::query()->findOrFail($data['tournament_id']);
        $t->update([
            'title' => $data['title'],
            'start_at' => $data['start_at'],
            'status' => $data['status'],
        ]);

        return back()->with('status', 'Tournament updated.');
    }

    public function activate(Request $request)
    {
        $t = WhippleTournament::query()->findOrFail($request->tournament_id);
        $t->update(['status' => 'active']);

        return back()->with('status', 'Tournament marked active.');
    }

    public function complete(Request $request)
    {
        $t = WhippleTournament::query()->findOrFail($request->tournament_id);
        $t->update(['status' => 'completed']);

        return back()->with('status', 'Tournament completed.');
    }

    public function import(Request $request)
    {
        $data = $request->validate([
            'tournament_id' => 'required|exists:whipple_tournaments,id',
            'source' => 'required|in:frequent,wins,both',
            'limit' => 'required|integer|min:1|max:100',
        ]);
        $t = WhippleTournament::query()->findOrFail($data['tournament_id']);

        if ($data['source'] === 'both') {
            $n1 = $this->tournaments->importFromLeaderboard($t, 'frequent', (int) $data['limit']);
            $n2 = $this->tournaments->importFromLeaderboard($t, 'wins', (int) $data['limit']);
            $n = $n1 + $n2;
        } else {
            $n = $this->tournaments->importFromLeaderboard($t, $data['source'], (int) $data['limit']);
        }

        return back()->with('status', "Imported {$n} qualified players.");
    }

    public function addPlayer(Request $request)
    {
        $data = $request->validate([
            'tournament_id' => 'required|exists:whipple_tournaments,id',
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);
        $t = WhippleTournament::query()->findOrFail($data['tournament_id']);

        foreach ($data['user_ids'] as $uid) {
            $this->tournaments->addManualPlayerById($t, (int) $uid);
        }

        return back()->with('status', count($data['user_ids']) . ' players added.');
    }

    public function removePlayer(Request $request)
    {
        $data = $request->validate([
            'tournament_id' => 'required|exists:whipple_tournaments,id',
            'user_id' => 'required|exists:users,id',
        ]);
        $t = WhippleTournament::query()->findOrFail($data['tournament_id']);
        $this->tournaments->removePlayer($t, (int) $data['user_id']);

        return back()->with('status', 'Player removed.');
    }

    public function createLobby(Request $request)
    {
        $data = $request->validate([
            'tournament_id' => 'required|exists:whipple_tournaments,id',
            'game_key' => 'required|in:quiz,tap_rush,math_clash,color_switch,defuse_x',
            'label' => 'nullable|string|max:255',
        ]);
        $t = WhippleTournament::query()->findOrFail($data['tournament_id']);
        $t->update(['status' => 'active']);
        $this->tournaments->createLobby($t, $data['game_key'], $data['label'] ?? null);

        return back()->with('status', 'Lobby created.');
    }

    public function lobbyPlayers(Request $request)
    {
        $data = $request->validate([
            'lobby_id' => 'required|exists:whipple_tournament_lobbies,id',
        ]);
        $lobby = \App\Models\WhippleTournamentLobby::query()->findOrFail($data['lobby_id']);
        $match = SkillGameMatch::query()->where('tournament_id', $lobby->tournament_id)->where('lobby_id', $lobby->id)->first();

        $playerVirtual = WhippleTournamentPlayer::query()->where('tournament_id', $match->tournament_id)->where('eliminated', 0)->get();

        $n = 0;
        foreach ($playerVirtual as $pv) {
            $user = User::query()->where('id', $pv->user_id)->first();
            // if($user->referral_code == "demo"){
            $this->matchService->addPlayerToMatch($match, $user, "eliminated");
            $n++;
            // }
        }

        return back()->with('status', "Added {$n} players to lobby.");
    }

    public function startCountdown(Request $request)
    {
        $data = $request->validate([
            'lobby_id' => 'required|exists:whipple_tournament_lobbies,id',
        ]);
        $lobby = \App\Models\WhippleTournamentLobby::query()->findOrFail($data['lobby_id']);
        $n = $this->tournaments->addPlayersToLobby($lobby);
        $this->tournaments->startCountdown($lobby);

        $match = SkillGameMatch::query()->where('tournament_id', $lobby->tournament_id)->where('lobby_id', $lobby->id)->first();
        $match->update([
            'created_at' => now(),
            'started_at' => now(),
            'status' => "waiting"
        ]);

        return back()->with('status', '20s countdown started for lobby.');
    }

    public function endLobby(Request $request)
    {
        $data = $request->validate([
            'lobby_id' => 'required|exists:whipple_tournament_lobbies,id',
        ]);
        $lobby = \App\Models\WhippleTournamentLobby::query()->findOrFail($data['lobby_id']);
        $this->tournaments->endLobby($lobby);

        return back()->with('status', 'Lobby ended. Scores ranked.');
    }

    public function eliminate(Request $request)
    {
        $data = $request->validate([
            'tournament_id' => 'required|exists:whipple_tournaments,id',
            'count' => 'required|integer|min:1|max:500',
        ]);
        $t = WhippleTournament::query()->findOrFail($data['tournament_id']);
        $n = $this->tournaments->eliminateBottom($t, (int) $data['count']);

        return back()->with('status', "Eliminated {$n} players from bottom of last ended lobby.");
    }

    public function commentary(Request $request)
    {
        $data = $request->validate([
            'tournament_id' => 'required|exists:whipple_tournaments,id',
            'body' => 'required|string|max:5000',
            'send_type' => 'required|in:normal,key_moment',
        ]);
        $t = WhippleTournament::query()->findOrFail($data['tournament_id']);
        $this->tournaments->postCommentary(
            $t,
            $data['body'],
            $data['send_type'] === 'key_moment',
            $request->user()->id
        );

        return back()->with('status', 'Commentary posted.');
    }

    public function streamSlot(Request $request)
    {
        $data = $request->validate([
            'tournament_id' => 'required|exists:whipple_tournaments,id',
            'slot' => 'required|in:1,2',
            'user_id' => 'required|exists:users,id',
        ]);
        $t = WhippleTournament::query()->findOrFail($data['tournament_id']);
        $this->tournaments->setStreamSlot($t, (int) $data['slot'], (int) $data['user_id']);

        return back()->with('status', 'Stream slot updated.');
    }

    public function reset(Request $request)
    {
        $data = $request->validate([
            'tournament_id' => 'required|exists:whipple_tournaments,id',
        ]);
        $t = WhippleTournament::query()->findOrFail($data['tournament_id']);
        $this->tournaments->resetTournament($t);

        return back()->with('status', 'Tournament reset to defaults.');
    }
}
