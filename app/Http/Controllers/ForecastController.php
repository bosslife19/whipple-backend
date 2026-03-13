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
        if($type){
            return ForecastMatch::where('type',$type)->where('status','active')
            ->where('kickoff_time','>', now()->addMinutes(10))
            ->get();
        }
        return ForecastMatch::where('status','active')
            ->where('kickoff_time','>', now()->addMinutes(10))
            ->get();
    }

    public function submit(Request $request)
    {
        $request->validate([
            'label' => 'nullable|string',
            'type' => 'required|string',
            'matches' => 'required',
        ]);

        $user = User::find(Auth::user()->id);

        if($request->status == "active"){
            $forecastRound = ForecastRound::where('user_id',Auth::user()->id)->where('status','active')->orWhere('status', 'closed')->get();
            if($forecastRound->count() > 0){
                if ($user->wallet_balance < 500) {
                    return response()->json(['message' => 'Insufficient balance'], 422);
                }else{
                    $after = $user->wallet_balance - 500;
                    Transaction::create([
                        'user_id' => $user->id,
                        'type' => 'game',
                        'amount' => 500,
                        'status' => 'completed',
                        'ref' => uniqid(),
                        'description' => 'Forecast game '. $request->type,
                        'balance_before' => $user->wallet_balance,
                        'balance_after' => $after
                    ]);
                    $user->update(['wallet_balance' => $after]);
                }   
            }            
        }

        return DB::transaction(function () use ($request) {
            $forecastRound = ForecastRound::create([
                'label' => uniqid(),
                'user_id' => Auth::user()->id,
                'type' => $request->type,
                'status' => $request->status ?? 'draft',
            ]);

            $matches = is_array($request->matches) && !isset($request->matches['id']) 
                ? $request->matches 
                : [$request->matches];

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
                    'id' => $m->id,
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

