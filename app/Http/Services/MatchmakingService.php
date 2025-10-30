<?php

namespace App\Http\Services;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Wallet;
use App\Models\SkillGame;
use App\Models\Transaction;
use Illuminate\Support\Str;
use App\Models\SkillGameMatch;
use Illuminate\Support\Facades\DB;
use App\Models\SkillGameMatchPlayers;

class MatchmakingService
{
    protected $platformFeeRate = 0.20; // 20%

    /**
     * Find or create a waiting match for a game
     */
    public function findOrCreateWaitingMatch(SkillGame $game)
    {
        $match = SkillGameMatch::where('game_id', $game->id)
            ->where('status', 'waiting')
            ->orderBy('created_at', 'asc')
            ->first();

        if (!$match) {
            $match = SkillGameMatch::create([
                'game_id' => $game->id,
                'status' => 'waiting',
                'max_players' => 4,
                'match_time_window' => 30,
                'countdown' => 5,
                'started_at' => Carbon::now()->addSeconds(30),
                'meta' => null,
            ]);
        }

        return $match;
    }

    /**
     * Deduct stake from user's wallet and add to match.
     */
    public function addPlayerToMatch(SkillGameMatch $match, User $user)
    {
        $stake = $match->game->stake;

        return DB::transaction(function () use ($stake, $match, $user) {
            if ($user->wallet_balance < $stake) {
                return response()->json(['message' => 'Insufficient balance'], 422);
            }
            $before = $user->wallet_balance;
            $after = $before - $stake;

            Transaction::create([
                'user_id' => $user->id,
                'type' => 'game',
                'amount' => $stake,
                'status' => 'completed',
                'ref' => uniqid(),
                'description' => 'Skill game - ' . $match->game->name,
                'balance_before' => $user->wallet_balance,
                'balance_after' => $after
            ]);

            $user->update(['wallet_balance' => $after]);

            // add match player
            $existing = SkillGameMatchPlayers::where('match_id', $match->id)
                ->where('user_id', $user->id)
                ->first();

            if ($existing) {
                // Already joined
                return $existing;
            }

            $player = SkillGameMatchPlayers::create([
                'match_id' => $match->id,
                'user_id' => $user->id,
                'stake_paid' => $stake,
                'score' => 0,
                'has_submitted' => false,
            ]);

            return $player;
        });
    }

    /**
     * Resolve the match: compute payouts, return array of operations
     */
    public function resolveMatch(SkillGameMatch $match)
    {
        // load players
        $players = $match->players()->with('user')->get();

        // compute pot
        $totalPot = $players->sum('stake_paid');
        $platformFee = (int)floor($totalPot * $this->platformFeeRate);

        $payoutable = $totalPot - $platformFee;

        // Sort players by score (assume score filled)
        $sorted = $players->sortByDesc(function ($p) {
            return $p->score ?? 0;
        })->values();

        // Determine winners (top1 and top2). Ties share as per doc
        $topScore = $sorted[0]->score ?? 0;
        $topPlayers = $sorted->filter(fn($p) => ($p->score ?? 0) === $topScore);

        $results = [];
        if ($topPlayers->count() > 1) {
            // tie among top players -> share pot equally among top two? doc says first two share equally if tie
            // We'll split between the top two players who tied; if more than 2 tie, split among top 2 positions present
            $share = (int)floor($payoutable / $topPlayers->count());
            foreach ($topPlayers as $p) {
                $results[] = ['user_id' => $p->user_id, 'amount' => $share];
            }
        } else {
            // clear top1 and runner up if available
            $winner = $sorted->get(0);
            $runner = $sorted->get(1) ?? null;

            if ($winner) {
                $winnerAmt = (int)floor($payoutable * 0.75);
                $results[] = ['user_id' => $winner->user_id, 'amount' => $winnerAmt];
            }
            if ($runner) {
                $runnerAmt = (int)floor($payoutable * 0.25);
                $results[] = ['user_id' => $runner->user_id, 'amount' => $runnerAmt];
            }
        }

        // return structure for controller to perform DB ops
        return [
            'total_pot' => $totalPot,
            'platform_fee' => $platformFee,
            'payouts' => $results,
            'players_sorted' => $sorted,
        ];
    }

    /**
     * Apply payouts (credit wallets) - atomic
     */
    public function applyPayouts(array $payouts)
    {
        return DB::transaction(function () use ($payouts) {
            $operations = [];
            foreach ($payouts as $p) {
                $wallet = User::firstOrCreate(['user_id' => $p['user_id']], ['balance' => 0]);
                $wallet->balance += $p['amount'];
                $wallet->save();

                $tx = Transaction::create([
                    'wallet_id' => $wallet->id,
                    'type' => 'payout',
                    'amount' => $p['amount'],
                    'reference' => 'match_payout_' . \Illuminate\Support\Str::random(8),
                    'meta' => null,
                ]);
                $operations[] = $tx;
            }
            return $operations;
        });
    }

    public function  matchStatus($matchId)
    {
        $match = SkillgameMatch::with('players.user')->findOrFail($matchId);
        $maxPlayers = $match->max_players ?? 4;
        $timeLeft = max(0, Carbon::parse($match->started_at)->diffInSeconds(now(), false));
        if (Carbon::parse($match->started_at)->lessThan(Carbon::now())) {
            $remaining = true;
        } else {
            $remaining =  $timeLeft <= 5 ? true : false;
        }

        // If less than 5 seconds left, fill missing slots with demo users
        if ($remaining && $match->status === 'waiting') {
            $currentCount = $match->players->count();

            if ($currentCount < $maxPlayers) {
                $needed = $maxPlayers - $currentCount;

                $demoUsers = User::where('referral_code', 'demo')
                    ->whereNotIn('id', $match->players->pluck('user_id'))
                    ->inRandomOrder()
                    ->take($needed)
                    ->get();

                foreach ($demoUsers as $demo) {
                    SkillGameMatchPlayers::firstOrCreate([
                        'match_id' => $match->id,
                        'user_id' => $demo->id,
                    ], [
                        'stake_paid' => 0,
                        'status' => 'joined',
                        'has_submitted' => false,
                        'is_demo' => true,
                        'score' => 0,
                    ]);
                }

                // refresh relation
                $match->load('players.user');
            }

            // once full, start game automatically
            if ($match->players->count() >= $maxPlayers) {
                // $this->startMatch($match);
            }
        }

        $matchB = [
            "id" => $match->id,
            "game_id" => $match->game_id,
            "max_players" => $match->max_players,
            "match_time_window" => $match->match_time_window,
            "countdown" => $match->countdown,
            "status" => $match->status,
            "pot_amount" => $match->pot_amount,
            "platform_fee_percent" => $match->platform_fee_percent,
            "started_at" => $match->started_at,
            "finished_at" => $match->finished_at,
            "meta" => $match->meta,
        ];

        $players = [];
        foreach ($match->players as $ply) {
            $players[] = [
                "id" => $ply->id,
                "match_id" => $ply->match_id,
                "user_id" => $ply->user->name,
                "stake_paid" => $ply->stake_paid,
                "status" => $ply->status,
                "has_submitted" => $ply->has_submitted,
                "scores" => $ply->scores,
                "score" => $ply->score,
                "rank" => $ply->rank,
                "time" => $ply->time,
            ];
        }

        return [
            'match' => $matchB,
            'players' => $players
        ];
    }
}
