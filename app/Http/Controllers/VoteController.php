<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Vote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VoteController extends Controller
{
    public function submitVote(Request $request){
        $gameId = $request->gameId; // or request/game context

$totalVotes = Vote::where('game_id', $gameId)->count();

if ($totalVotes >= 9) {
return response()->json(['status'=>false, 'error'=>'Voting is closed for this game']);
}
        try {
            $request->validate(['vote'=>'required|string']);
            $vote = new Vote();
            $vote->vote = $request->vote;
            $vote->player_id = $request->user()->id;
            $vote->game_id = $request->gameId;
            $vote->save();
            $totalVote = Vote::where('game_id', $request->gameId)->count();

            if($totalVote ==9){
                $mostVoted = Vote::where('game_id', $request->gameId)
    ->select('vote', DB::raw('COUNT(*) as total'))
    ->groupBy('vote')
    ->orderByDesc('total')
    ->first();

if ($mostVoted) {
    $winningChoice = $mostVoted->vote; // 'one', 'two', or 'three'
    $game = Game::where('id', $request->gameId);
    $game->number_of_winners = $winningChoice;
    $game->save();
}
            }

            return response()->json(['status'=>true]);
        } catch (\Throwable $th) {
            return response()->json(['error'=>$th->getMessage()]);
        }
    }

    public function losersVote(Request $request){
                try {
            $request->validate(['vote'=>'required|string']);
            $vote = new Vote();
            $vote->vote = $request->vote;
            $vote->player_id = $request->user()->id;
            $vote->game_id = $request->gameId;
            $vote->is_loser = true;
            $vote->save();

            return response()->json(['status'=>true]);
        } catch (\Throwable $th) {
            return response()->json(['error'=>$th->getMessage()]);
        }
    }
}
