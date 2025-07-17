<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\Request;

class GameController extends Controller
{
    public function createGame(Request $request){
        try {
            $request->validate(['name'=>'required']);

            if($request->name =='Lucky Number'){
                Game::create([
                    'creator_id'=>$request->user()->id,
                    'name'=>$request->name,
                    'number_result'=>$request->result,
                    'category'=>$request->category,
                    'subcategory'=>$request->subcategory,
                    'odds'=>$request->odds,
                    'stake'=>$request->stake

                ]);

                return response()->json(['status'=>true], 200);
            }

        } catch (\Throwable $th) {
            return response()->json(['error'=>$th->getMessage()]);
        }
    }

    public function getAllGames(Request $request){
        $games = Game::latest()->with('creator')->get();

        return response()->json(['games'=>$games, 'status'=>true]);
    }

    public function getGame($id){
        $game = Game::with('creator')->find($id);

        return response()->json(['status'=>true, 'game'=>$game], 200);
    }

    public function playGame(Request $request){
        $request->validate(['gameId'=>'required', 'choiceNumber'=>'required']);

        $game = Game::find($request->gameId);
$isAttached = $game->players()->where('user_id', $request->user()->id)->exists();

if ($isAttached) {
return response()->json(['error'=>'You have already played this game']);
}
    $game->players()->attach($request->user()->id);

    if($game->number_result == $request->choiceNumber){
        return response()->json(['status'=>true, 'success'=>true]);
    }else{
        return response()->json(['status'=>true, 'success'=>false]);
    }

    }
}
