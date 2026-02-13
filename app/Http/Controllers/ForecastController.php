<?php

namespace App\Http\Controllers;

use App\Models\Forecast;
use Illuminate\Http\Request;
use App\Models\ForecastMatch;
use App\Models\ForecastRound;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ForecastController extends Controller
{
    public function list($type)
    {
        return ForecastMatch::where('type',$type)->where('status','active')
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

        return DB::transaction(function () use ($request) {
            $forecastRound = ForecastRound::create([
                'label' => uniqid(),
                'user_id' => Auth::user()->id,
                'type' => $request->type,
                'status' => $request->status ?? 'pending',
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
                    'score_b' => $match['score_b'] ?? null
                ]);
            }

            return response()->json(['status' => 'success', 'message' => 'Forecast saved'], 200);
        });
    }

    public function myForecasts()
    {
        return Forecast::where('user_id',Auth::user()->id)->with('match')->get();
    }
}

