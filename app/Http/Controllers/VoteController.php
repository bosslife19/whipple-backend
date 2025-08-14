<?php

namespace App\Http\Controllers;

use App\Models\Vote;
use Illuminate\Http\Request;

class VoteController extends Controller
{
    public function submitVote(Request $request){
        try {
            $request->validate(['vote'=>'required|string']);
            $vote = new Vote();
            $vote->vote = $request->vote;
            $vote->player_id = $request->user()->id;
            $vote->game_id = $request->gameId;
            $vote->save();

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
