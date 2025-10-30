<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\SkillGame;
use App\Models\GameRecord;
use Illuminate\Http\Request;
use App\Models\SkillGameMatch;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\SkillGameMatchPlayers;
use App\Http\Services\MatchmakingService;

class SkillgameController extends Controller
{
    protected $matchService;

    public function __construct(MatchmakingService $matchService)
    {
        $this->matchService = $matchService;
    }

    // GET /skillgame/games
    public function index()
    {
        $games = SkillGame::all(['id', 'key', 'name', 'stake', 'duration', 'meta']);
        return response()->json(['data' => $games]);
    }

    // GET /skillgame/games/{key}
    public function show($key)
    {
        $game = SkillGame::where('key', $key)->first();
        if (!$game) return response()->json(['error' => 'Game not found'], 404);
        return response()->json(['data' => $game]);
    }

    /**
     * Player joins a match for a game.
     * POST /skillgame/matches/join
     * body: { game_key: string, user_id: int }
     */
    public function join($key)
    {

        $game = SkillGame::where('key', $key)->first();
        $user = User::find(Auth::user()->id);

        if ($user->wallet_balance < $game->stake) {
            return response()->json(['message' => 'Insufficient balance'], 422);
        }

        DB::beginTransaction();
        try {
            $match = $this->matchService->findOrCreateWaitingMatch($game);

            // Check if match already full
            $currentCount = $match->players()->count();
            if ($currentCount >= $match->max_players) {
                DB::rollBack();
                return response()->json(['error' => 'Match full'], 400);
            }

            // Deduct stake & add player
            $player = $this->matchService->addPlayerToMatch($match, $user);

            // If after join we reached max players, update match status to countdown or playing
            $newCount = $match->players()->count();
            if ($newCount >= $match->max_players) {
                $match->status = 'countdown';
                $match->save();
                // In production you might queue a job to call forceStart after $match->countdown seconds
            }
            DB::commit();

            return response()->json([
                'match' => $match->load('players'),
                'player' => $player,
            ], 201);
        } catch (\Exception $ex) {
            DB::rollBack();
            return response()->json(['error' => $ex->getMessage()], 400);
        }
    }

    public function status($matchId)
    {
        $match = $this->matchService->matchStatus($matchId);
        return response()->json($match);        
    }

    public function start($matchId)
    {
        $match = $this->matchService->matchStatus($matchId);
        SkillgameMatch::findOrFail($matchId)->update(['status' => "started"]);
        $match['match']['status'] = "started";
        return response()->json($match);
    }

    public function updateScore(Request $request)
    {
        $user = $request->user(); // authenticated user

        // $request->validate([
        //     'score' => 'required|numeric|min:0',
        // ]);

        // Find match
        $match = SkillgameMatch::with('players.user')->findOrFail($request->matchId);

        // Check match status
        if ($match->status == 'waiting') {
            $match->update(['status' => "started"]);
        }

        // Find player's record in this match
        $player = SkillGameMatchPlayers::where('match_id', $match->id)
            ->where('user_id', $user->id)
            ->first();

        // if (!$player) {
        //     return response()->json(['error' => 'Player not found in match.'], 404);
        // }

        // Update player's score
        $player->score = $request->score;
        $player->save();

        // Simulate demo players increasing their scores randomly
        $demoPlayers = SkillGameMatchPlayers::where('match_id', $match->id)
            ->where('is_demo', true)
            ->get();

        foreach ($demoPlayers as $demo) {
            $increase = rand(5, 25); // simulate a small random increase
            $demo->score += $increase;
            $demo->save();
        }

        // Reload all players
        $players = SkillGameMatchPlayers::with('user')
            ->where('match_id', $match->id)
            ->orderByDesc('score')
            ->get();

        // Assign rank
        $rankedPlayers = $players->map(function ($p, $index) {
            $p->update([
                "rank" => $index + 1,
                "score" => $p->score
            ]);
            return [
                'rank' => $index + 1,
                'id' => $p->id,
                'name' => $p->user->name,
                'score' => $p->score,
            ];
        });

        return response()->json([
            'status' => 'success',
            'match_id' => $match->id,
            'leaderboard' => $rankedPlayers,
        ]);
    }

    public function complete(Request $request)
    {
        SkillGameMatchPlayers::where('user_id', Auth::user()->id)->where('match_id', $request->matchId)->update([
            "status" => "finished",
            "score" => $request->score,
            "time" => $request->time,
            "has_submitted" => true,
        ]);
        $match = SkillgameMatch::findOrFail($request->matchId);
        return response()->json($match);
    }

    // POST /skillgame/matches/{match}/leave
    public function leave(Request $request, $match)
    {
        // $this->validate($request, [
        //     'user_id' => 'required|integer|exists:users,id',
        // ]);
        $userId = $request->user_id;
        $SkillGameMatch = SkillGameMatch::find($match);
        if (!$SkillGameMatch) return response()->json(['error' => 'Match not found'], 404);

        $mp = SkillGameMatchPlayers::where('match_id', $match)->where('user_id', $userId)->first();
        if (!$mp) return response()->json(['error' => 'Not in match'], 400);

        // Refund stake when leaving while waiting
        if ($SkillGameMatch->status === 'waiting') {
            DB::transaction(function () use ($mp) {
                // refund
                $wallet = $mp->user->wallet ?? null;
                if ($wallet) {
                    $wallet->balance += $mp->stake_paid;
                    $wallet->save();
                    $wallet->transactions()->create([
                        'type' => 'refund',
                        'amount' => $mp->stake_paid,
                        'reference' => 'match_refund_' . \Illuminate\Support\Str::random(6),
                    ]);
                }
                $mp->delete();
            });
        } else {
            // can't leave when playing
            return response()->json(['error' => 'Cannot leave a match in progress'], 400);
        }

        return response()->json(['message' => 'Left match and refunded stake']);
    }

    // GET /skillgame/matches/{match}
    public function showMatch($match)
    {
        $m = SkillGameMatch::with('game', 'players.user')->find($match);
        if (!$m) return response()->json(['error' => 'Match not found'], 404);
        return response()->json(['data' => $m]);
    }

    /**
     * Client submits result
     * POST /skillgame/matches/{match}/submit
     * body: { user_id, score, meta }
     */
    public function submitResult(Request $request, $match)
    {
        // $this->validate($request, [
        //     'user_id' => 'required|integer|exists:users,id',
        //     'score' => 'required|numeric|min:0',
        //     'meta' => 'nullable|array',
        // ]);

        $m = SkillGameMatch::find($match);
        if (!$m) return response()->json(['error' => 'Match not found'], 404);

        $mp = SkillGameMatchPlayers::where('match_id', $m->id)->where('user_id', $request->user_id)->first();
        if (!$mp) return response()->json(['error' => 'Not a participant'], 400);

        // Record player's submission
        DB::transaction(function () use ($mp, $request, $m) {
            $mp->score = (int)$request->score;
            $mp->result_meta = $request->meta ?? null;
            $mp->has_submitted = true;
            $mp->save();

            // GameRecord::create([
            //     'match_id' => $m->id,
            //     'user_id' => $mp->user_id,
            //     'score' => (int)$request->score,
            //     'meta' => $request->meta ?? null,
            // ]);

            // If everyone submitted (or match status indicates), finalize
            $all = $m->players()->count();
            $submitted = $m->players()->where('has_submitted', true)->count();

            if ($submitted >= $all) {
                // finalize match
                $this->finalizeMatch($m);
            }
        });

        return response()->json(['message' => 'Result submitted']);
    }

    // Admin: force start match (can be used to start countdown or move to playing)
    public function forceStart(Request $request, $match)
    {
        $m = SkillGameMatch::find($match);
        if (!$m) return response()->json(['error' => 'Match not found'], 404);

        if ($m->status !== 'waiting' && $m->status !== 'countdown') {
            return response()->json(['error' => 'Match cannot be force started'], 400);
        }

        // Mark as playing now (in real system this might trigger real-time events)
        $m->status = 'playing';
        $m->save();

        return response()->json(['message' => 'Match started', 'match' => $m]);
    }

    // internal finalize function: compute payouts and update DB
    protected function finalizeMatch(SkillGameMatch $m)
    {
        // compute and apply payouts
        $result = $this->matchService->resolveMatch($m);

        // apply payouts
        $this->matchService->applyPayouts($result['payouts']);

        // mark match finished and store ranking meta
        $m->status = 'finished';
        $m->meta = [
            'payouts' => $result['payouts'],
            'platform_fee' => $result['platform_fee'],
            'total_pot' => $result['total_pot'],
        ];
        $m->save();

        // optionally create game_records already done on submit
        return $result;
    }
}
