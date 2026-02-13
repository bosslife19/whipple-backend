<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\User;
use App\Models\Forecast;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\ForecastMatch;
use App\Models\ForecastRound;

class AdminController extends Controller
{
    public function index()
    {
        $metrics = [
            'total_users' => User::count(),
            'total_games' => Game::count(),
            'total_transactions' => Transaction::count(),
            'total_volume' => Transaction::where('status', 'success')->sum('amount'),
            'recent_users' => User::latest()->take(5)->get(),
        ];

        return view('admin.dashboard', compact('metrics'));
    }

    public function forecast(Request $request)
    {
        $query = ForecastMatch::query();
        $metricsQuery = ForecastMatch::query();

        if ($request->filled('start_date')) {
            $query->whereDate('kickoff_time', '>=', $request->start_date);
            $metricsQuery->whereDate('kickoff_time', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('kickoff_time', '<=', $request->end_date);
            $metricsQuery->whereDate('kickoff_time', '<=', $request->end_date);
        }

        $matches = $query->latest()->get();
        
        $metrics = [
            'all' => [
                'draft' => (clone $metricsQuery)->where('status', 'draft')->count(),
                'active' => (clone $metricsQuery)->where('status', 'active')->count(),
                'ended' => (clone $metricsQuery)->where('status', 'ended')->count(),
            ],
            'general' => [
                'draft' => (clone $metricsQuery)->where('type', 'general')->where('status', 'draft')->count(),
                'active' => (clone $metricsQuery)->where('type', 'general')->where('status', 'active')->count(),
                'ended' => (clone $metricsQuery)->where('type', 'general')->where('status', 'ended')->count(),
            ],
            'specific' => [
                'draft' => (clone $metricsQuery)->where('type', 'specific')->where('status', 'draft')->count(),
                'active' => (clone $metricsQuery)->where('type', 'specific')->where('status', 'active')->count(),
                'ended' => (clone $metricsQuery)->where('type', 'specific')->where('status', 'ended')->count(),
            ]
        ];

        return view('admin.forecast', compact('matches', 'metrics'));
    }


    public function storeForecastMatch(Request $request)
    {
        $data = $request->validate([
            'team_name_a' => 'required|string',
            'team_name_b' => 'required|string',
            'team_logo_a' => 'nullable|string',
            'team_logo_b' => 'nullable|string',
            'kickoff_time' => 'required|date',
            'type' => 'required|in:general,specific',
            'status' => 'required|in:draft,active,ended',
            'score_a' => 'nullable|integer',
            'score_b' => 'nullable|integer',
        ]);

        if ($data['status'] === 'ended' && isset($data['score_a']) && isset($data['score_b'])) {
            $data['result_a'] = $data['score_a'] > $data['score_b'] ? 'win' : ($data['score_a'] == $data['score_b'] ? 'draw' : 'loss');
            $data['result_b'] = $data['score_b'] > $data['score_a'] ? 'win' : ($data['score_b'] == $data['score_a'] ? 'draw' : 'loss');
        }

        ForecastMatch::create($data);

        return back()->with('status', 'Match created successfully!');
    }

    public function updateForecastMatch(Request $request, $id)
    {
        $match = ForecastMatch::findOrFail($id);
        
        $data = $request->validate([
            'team_name_a' => 'required|string',
            'team_name_b' => 'required|string',
            'team_logo_a' => 'nullable|string',
            'team_logo_b' => 'nullable|string',
            'kickoff_time' => 'required|date',
            'type' => 'required|in:general,specific',
            'status' => 'required|in:draft,active,ended',
            'score_a' => 'nullable|integer',
            'score_b' => 'nullable|integer',
        ]);

        if ($data['status'] === 'ended' && isset($data['score_a']) && isset($data['score_b'])) {
            $data['result_a'] = $data['score_a'] > $data['score_b'] ? 'win' : ($data['score_a'] == $data['score_b'] ? 'draw' : 'loss');
            $data['result_b'] = $data['score_b'] > $data['score_a'] ? 'win' : ($data['score_b'] == $data['score_a'] ? 'draw' : 'loss');
        }

        $match->update($data);

        // If it's ended, we should process rewards
        if ($match->status === 'ended') {
            $this->processForecastResults($match);
        }

        return back()->with('status', 'Match updated successfully!');
    }

    private function processForecastResults($match)
    {
        $forecasts = Forecast::where('match_id', $match->id)->get();

        foreach ($forecasts as $f) {
            $correct = false;

            if ($f->type === 'general') {
                $correct = $f->choice_a === $match->result_a && $f->choice_b === $match->result_b;
            } else {
                $correct =
                    $f->score_a == $match->score_a &&
                    $f->score_b == $match->score_b;
            }

            $f->update(['is_correct' => $correct]);
        }

        $this->awardPoints($match->forecast_round_id);
    }



    public function exportForecastMatches(Request $request)
    {
        $query = ForecastMatch::query();

        if ($request->filled('start_date')) {
            $query->whereDate('kickoff_time', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('kickoff_time', '<=', $request->end_date);
        }

        $matches = $query->latest()->get();
        $filename = "forecast_matches_" . date('Ymd_His') . ".csv";
        
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['team_logo_a', 'team_logo_b', 'team_name_a', 'team_name_b', 'kickoff_time', 'type', 'result_a', 'result_b', 'score_a', 'score_b', 'status', 'id'];

        $callback = function() use($matches, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($matches as $match) {
                $row = [];
                foreach ($columns as $column) {
                    $row[] = $match->$column;
                }
                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportForecastTemplate()
    {
        $filename = "forecast_template.csv";
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['team_logo_a', 'team_logo_b', 'team_name_a', 'team_name_b', 'kickoff_time', 'type', 'result_a', 'result_b', 'score_a', 'score_b', 'status'];

        $callback = function() use($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function importForecastMatches(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt,xlsx',
            'status_override' => 'nullable|in:draft,active,ended'
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), "r");
        $header = fgetcsv($handle);
        
        $count = 0;
        while (($row = fgetcsv($handle)) !== FALSE) {
            $data = array_combine($header, $row);
            
            if ($request->filled('status_override')) {
                $data['status'] = $request->status_override;
            }

            // Automate results if ended but no results provided
            if ($data['status'] === 'ended' && isset($data['score_a']) && isset($data['score_b'])) {
                if (empty($data['result_a'])) {
                    $data['result_a'] = $data['score_a'] > $data['score_b'] ? 'win' : ($data['score_a'] == $data['score_b'] ? 'draw' : 'loss');
                }
                if (empty($data['result_b'])) {
                    $data['result_b'] = $data['score_b'] > $data['score_a'] ? 'win' : ($data['score_b'] == $data['score_a'] ? 'draw' : 'loss');
                }
            }

            ForecastMatch::create($data);
            $count++;
        }
        fclose($handle);

        $this->awardPoints();

        return back()->with('status', "$count matches imported successfully!");
    }

    public function uploadResult(Request $request)
    {
        $match = ForecastMatch::findOrFail($request->match_id);

        $match->update([
            'result'=>$request->result,
            'score_a'=>$request->score_a,
            'score_b'=>$request->score_b,
        ]);

        $forecasts = Forecast::where('match_id',$match->id)->get();

        foreach ($forecasts as $f) {
            $correct = false;

            if ($match->type === 'general') {
                $correct = $f->choice === $match->result;
            } else {
                $correct =
                    $f->score_a == $match->score_a &&
                    $f->score_b == $match->score_b;
            }

            $f->update(['is_correct'=>$correct, 'status'=>'scored']);
        }

        $this->awardPoints();
    }

    private function awardPoints()
    {
        $rounds = ForecastRound::where('status', 'active')->get();
        foreach ($rounds as $round) {
            $generalForecasts = Forecast::where('forecast_round_id', $round->id)->where('type', 'general')->get();
            $generalForecastsScored = Forecast::where('forecast_round_id', $round->id)->where('type', 'general')->where('status', 'scored')->get();
            $generalForecastsCorrect = Forecast::where('forecast_round_id', $round->id)->where('type', 'general')->where('is_correct', true)->get();
            if($generalForecasts->count() === $generalForecastsScored->count() ){
                if($generalForecasts->count() === $generalForecastsCorrect->count()){
                    $point = 20 * $generalForecasts->count();
                    $round->update(['winnings'=>$point]);
                    $this->transactionRecord($round->user_id, $point, $round);
                }

                $round->update(['status'=>'closed']);
            }


            $specificForecasts = Forecast::where('forecast_round_id', $round->id)->where('type', 'specific')->get();
            $specificForecastsScored = Forecast::where('forecast_round_id', $round->id)->where('type', 'specific')->where('status', 'scored')->get();
            $specificForecastsCorrect = Forecast::where('forecast_round_id', $round->id)->where('type', 'specific')->where('is_correct', true)->get();
            if($specificForecasts->count() === $specificForecastsScored->count() ){
                $point = 80 * $specificForecastsCorrect->count();
                $round->update(['winnings'=>$point]);
                $this->transactionRecord($round->user_id, $point, $round);
                $round->update(['status'=>'closed']);
            }
        }
    }

    private function transactionRecord($userId, $points, $round)
    {
        $user = User::find($userId);

        $afterBal = $user->whipple_points += $points;
        Transaction::create([
            'user_id' => $user->id,
            'type' => 'win',
            'amount' => $points,
            'status' => 'completed',
            'ref' => $ref ?? uniqid(),
            'description' => 'Forecast round - ' . $round->label,
            'balance_before' => $user->whipple_points,
            'balance_after' => $afterBal
        ]);

        $user->update(['whipple_points' => $afterBal]);
    }

}
