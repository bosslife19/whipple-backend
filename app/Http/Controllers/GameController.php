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

            if($request->name=="Flip The Coin"){
                Game::create([
                    'creator_id'=>$request->user()->id,
                    'name'=>$request->name,
                    'coin_toss'=>$request->result,
                    
                    'odds'=>$request->odds,
                    'stake'=>$request->stake
                ]);

                return response()->json(['status'=>true]);
            }
            if($request->name =='Dice Roll'){
                Game::create([
                    'creator_id'=>$request->user()->id,
                    'name'=>$request->name,
                    'dice_result'=>$request->diceRolled,
                    'stake'=>$request->stake,
                    'odds'=>$request->odds
                ]);
                return response()->json(['status'=>true]);
            }
            if($request->name =="Color Roulette"){
                Game::create([
                    'creator_id'=>$request->user()->id,
                    'name'=>$request->name,
                    'spin_wheel_result'=>$request->colors,
                    'stake'=>$request->stake
                ]);
                return response()->json(['status'=>true], 200);
            }
            if($request->name =="Color Roulette2"){
                Game::create([
                    'creator_id'=>$request->user()->id,
                    'name'=>$request->name,
                    'spin_wheel_result'=>$request->colorSpun,
                    'stake'=>$request->stake
                ]);
                return response()->json(['status'=>true], 200);
            }
            if($request->name =="One Number Spin"){
                 Game::create([
                    'creator_id'=>$request->user()->id,
                    'name'=>$request->name,
                    'number_wheel_result'=>$request->winningNumber,
                    'stake'=>$request->stake,
                    'odds'=>$request->odds
                ]);

                return response()->json(['status'=>true]);
            }
            if($request->name =='Goal Challenge'){
                Game::create([
                    'creator_id'=>$request->user()->id,
                    'name'=>$request->name,
                    'ball_direction'=>$request->direction,
                    'stake'=>$request->stake,
                    'odds'=>$request->odds
                ]);
                return response()->json(['status'=>true], 200);
            }

           if($request->name =='Mystery Box Game'){
                Game::create([
                    'creator_id'=>$request->user()->id,
                    'name'=>$request->name,
                    'winning_box'=>$request->boxSelected,
                    'stake'=>$request->stake,
                    'odds'=>$request->odds
                ]);
                return response()->json(['status'=>true], 200);
            }
             if($request->name =='Spin The Bottle'){
                Game::create([
                    'creator_id'=>$request->user()->id,
                    'name'=>$request->name,
                    'spin_bottle'=>$request->spinDirection,
                    'stake'=>$request->stake,
                    'odds'=>$request->odds
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
        $request->validate(['gameId'=>'required']);

        $game = Game::find($request->gameId);
$isAttached = $game->players()->where('user_id', $request->user()->id)->exists();

if ($isAttached) {
return response()->json(['error'=>'You have already played this game']);
}
    $game->players()->attach($request->user()->id);

    if( $game->name == 'Lucky Number'){
    if($game->number_result == $request->choiceNumber){
        return response()->json(['status'=>true, 'success'=>true]);
    }else{
        return response()->json(['status'=>true, 'success'=>false]);
    }
    }

    if($game->name == "Flip The Coin"){
        
        if($game->coin_toss == $request->choice){
            return response()->json(['status'=>true, 'success'=>true]);
        }else{
            return response()->json(['status'=>true, 'success'=>false]);
        }
    }
    if($game->name =="Goal Challenge"){
       
          if($game->ball_direction == $request->direction){
            return response()->json(['status'=>true, 'success'=>true]);
        }else{
            return response()->json(['status'=>true, 'success'=>false]);
        }
    }
    if($game->name =="Dice Roll"){
       
          if($game->dice_result == $request->numberRolled){
            return response()->json(['status'=>true, 'success'=>true]);
        }else{
            return response()->json(['status'=>true, 'success'=>false]);
        }
    }

     if($game->name =="One Number Spin"){
       
          if($game->number_wheel_result == $request->numberWheeled){
            return response()->json(['status'=>true, 'success'=>true]);
        }else{
            return response()->json(['status'=>true, 'success'=>false]);
        }
    }

    if($game->name =="Mystery Box Game"){
       
          if($game->winning_box == $request->boxSelected){
            return response()->json(['status'=>true, 'success'=>true]);
        }else{
            return response()->json(['status'=>true, 'success'=>false]);
        }
    }
    if($game->name =="Color Roulette2"){
                  if($game->spin_wheel_result == $request->colorSpun){
            return response()->json(['status'=>true, 'success'=>true]);
        }else{
            return response()->json(['status'=>true, 'success'=>false]);
        }
    }

    if($game->name =="Color Roulette"){
                  if($game->spin_wheel_result == $request->colorSpun){
            return response()->json(['status'=>true, 'success'=>true]);
        }else{
            return response()->json(['status'=>true, 'success'=>false]);
        }
    }

    if($game->name =="Spin The Bottle"){
                  if($game->spin_bottle == $request->direction){
            return response()->json(['status'=>true, 'success'=>true]);
        }else{
            return response()->json(['status'=>true, 'success'=>false]);
        }
    }




    }
}
