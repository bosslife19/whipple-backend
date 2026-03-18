<?php

namespace App\Http\Controllers;

use App\Models\Forecast;
use App\Models\ForecastMatch;
use App\Models\ForecastRound;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ForecastController extends Controller
{
    public function list($type = null)
    {
        $userId = Auth::id();

        // Use West African Time (e.g. Africa/Lagos) and only include
        // matches whose kickoff_time is more than 10 minutes from "now"
        $cutoff = now('Africa/Lagos')->addMinutes(10);

        $query = ForecastMatch::where('status', 'active')
            ->where('kickoff_time', '>', $cutoff);

        if ($type) {
            $query->where('type', $type);
        }

        // Exclude matches the authenticated user has already forecasted
        if ($userId) {
            $forecastQuery = Forecast::where('user_id', $userId);

            if ($type) {
                $forecastQuery->where('type', $type);
            }

            $forecastedMatchIds = $forecastQuery->pluck('match_id')->toArray();

            if (!empty($forecastedMatchIds)) {
                $query->whereNotIn('id', $forecastedMatchIds);
            }
        }

        return $query->get();
    }

    public function submit(Request $request)
    {
        $request->validate([
            'label' => 'nullable|string',
            'type' => 'required|string',
            'matches' => 'required',
        ]);

        $user = User::find(Auth::user()->id);

        if ($request->status === "active") {
            $userId = Auth::id();
            $today = now('Africa/Lagos')->toDateString();

            // Check if the user has already created a forecast round
            // of this type today (active or closed). If yes, charge.
            $amount = 0;
            // $hasForecastTodayForType = ForecastRound::where('user_id', $userId)
            //     ->where('type', $request->type)
            //     ->whereDate('created_at', $today)
            //     ->whereIn('status', ['active', 'closed'])
            //     ->exists();

            // if ($hasForecastTodayForType) {
            //     // Pricing per type: general => 500, specific => 1000
            //     $amount = $request->type === 'specific' ? 1000 : 500;
            // }

            $matches = is_array($request->matches) && !isset($request->matches['id'])
                ? $request->matches
                : [$request->matches];

            if($request->type == 'general'){
                $countGenralGame = ForecastRound::where('user_id', $userId)
                    ->where('type', 'general')
                    ->whereDate('created_at', $today)
                    ->whereIn('status', ['active', 'closed']);
                if($countGenralGame->count() == 1){
                    $gameCount = optional($countGenralGame->first())->forecast()->count() ?? 0;
                    if($gameCount == 1){
                        $amount = 500;
                    }
                }
                if ($countGenralGame->count() == 0) {
                    if (count($matches) > 1) {
                        $amount = 500;
                    }
                }
            }

            if($request->type == 'specific'){
                $countSpecificGame = ForecastRound::where('user_id', $userId)
                    ->where('type', 'specific')
                    ->whereDate('created_at', $today)
                    ->whereIn('status', ['active', 'closed']);

                if($countSpecificGame->count() >= 1){
                    $amount = 1000 * count($matches);                
                }
                if ($countSpecificGame->count() == 0) {
                    $amount = 1000 * (count($matches) - 1);
                }
            }

            if($amount > 0){
                if ($user->wallet_balance < $amount) {
                    return response()->json(['message' => 'Insufficient balance'], 422);
                }

                $after = $user->wallet_balance - $amount;

                Transaction::create([
                    'user_id'        => $user->id,
                    'type'           => 'game',
                    'amount'         => $amount,
                    'status'         => 'completed',
                    'ref'            => uniqid(),
                    'description'    => 'Forecast game ' . $request->type,
                    'balance_before' => $user->wallet_balance,
                    'balance_after'  => $after,
                ]);

                $user->update(['wallet_balance' => $after]);
            }
        }

        return DB::transaction(function () use ($request, $matches) {
            $forecastRound = ForecastRound::create([
                'label' => uniqid(),
                'user_id' => Auth::user()->id,
                'type' => $request->type,
                'status' => $request->status ?? 'draft',
            ]);

            foreach ($matches as $match) {
                Forecast::create([
                    'user_id' => Auth::user()->id,
                    'match_id' => $match['id'],
                    'forecast_round_id' => $forecastRound->id,
                    'choice_a' => $match['choice_a'] ?? null,
                    'choice_b' => $match['choice_b'] ?? null,
                    'score_a' => $match['score_a'] ?? null,
                    'score_b' => $match['score_b'] ?? null,
                    'type' => $request->type ?? 'general',
                ]);
            }

            return response()->json(['status' => 'success', 'message' => 'Forecast saved'], 200);
        });
    }

    public function myForecasts()
    {
        $forecasts = ForecastRound::where('user_id',Auth::user()->id)->orderBy('id','desc')->get();
        $data = [];
        foreach($forecasts as $forecast){            
            $match = Forecast::where('forecast_round_id', $forecast->id)->with('match')->get();
            $dataMatch = [];
            foreach ($match as $m) {
                $dataMatch[] = [
                    'id_round' => $m->id,
                    'id' => $m->match->id,
                    'team_logo_a' => $m->match->team_logo_a,
                    'team_name_a' => $m->match->team_name_a,
                    'match_result_a' => $m->match->result_a,
                    'match_score_a' => $m->match->score_a, 
                    'team_logo_b' => $m->match->team_logo_b,
                    'team_name_b' => $m->match->team_name_b,
                    'match_result_b' => $m->match->result_b,
                    'match_score_b' => $m->match->score_b, 
                    'match_kickoff_time' => $m->match->kickoff_time,
                    'match_type' => $m->match->type,                    
                    'match_status' => $m->match->status,
                    'forecast_choice_a' => $m->choice_a,
                    'forecast_choice_b' => $m->choice_b,
                    'forecast_score_a' => $m->score_a,
                    'forecast_score_b' => $m->score_b,
                    'forecast_is_correct' => $m->is_correct,
                    'forecast_status' => $m->status,
                    'forecast_created_at' => $m->created_at,
                ];
            }
            $data[] = [
                'id' => $forecast->id,
                'label' => $forecast->label,
                'type' => $forecast->type,
                'status' => $forecast->status,
                'winnings' => $forecast->winnings,
                'created_at' => $forecast->created_at,
                'match' => $dataMatch
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => $data
        ], 200);
    }
}

