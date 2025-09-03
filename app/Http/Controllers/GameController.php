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

                $user = $request->user();

                $user->wallet_balance = $user->wallet_balance - (intval($request->stake) + (intval($request->stake)*0.25));
                $user->save();


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
                                $user = $request->user();
                 $user->wallet_balance = $user->wallet_balance - (intval($request->stake) + (intval($request->stake)*0.25));
                                $user->save();
                return response()->json(['status'=>true]);
            }
            if($request->name =='Dice Roll'){
                Game::create([
                    'creator_id'=>$request->user()->id,
                    'name'=>$request->name,
                    'dice_result'=>$request->diceRolled,
                    'stake'=>$request->stake,
                    'odds'=>$request->odds,
                    'dice_type'=>$request->diceType
                ]);
                                $user = $request->user();
 $user->wallet_balance = $user->wallet_balance - (intval($request->stake) + (intval($request->stake)*0.25));
                                $user->save();
                return response()->json(['status'=>true]);
            }
            if($request->name =="Color Roulette"){
                Game::create([
                    'creator_id'=>$request->user()->id,
                    'name'=>$request->name,
                    'spin_wheel_result'=>$request->colors,
                    'odds'=>$request->odds,
                    'stake'=>$request->stake
                ]);
                 $user = $request->user();
                $user->wallet_balance = $user->wallet_balance - (intval($request->stake) + (intval($request->stake)*0.25));
                                $user->save();
                return response()->json(['status'=>true], 200);
            }
            if($request->name =="Color Roulette2"){
                Game::create([
                    'creator_id'=>$request->user()->id,
                    'name'=>$request->name,
                    'spin_wheel_result'=>$request->colorSpun,
                    'stake'=>$request->stake,
                    'odds'=>$request->odds
                ]);
                 $user = $request->user();
 $user->wallet_balance = $user->wallet_balance - (intval($request->stake) + (intval($request->stake)*0.25));
                                $user->save();
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
                 $user = $request->user();
                 $user->wallet_balance = $user->wallet_balance - (intval($request->stake) + (intval($request->stake)*0.25));
                                $user->save();

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
                 $user = $request->user();
                 $user->wallet_balance = $user->wallet_balance - (intval($request->stake) + (intval($request->stake)*0.25));
                                $user->save();
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
                 $user = $request->user();
 $user->wallet_balance = $user->wallet_balance - (intval($request->stake) + (intval($request->stake)*0.25));
                                $user->save();
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
                 $user = $request->user();
               $user->wallet_balance = $user->wallet_balance - (intval($request->stake) + (intval($request->stake)*0.25));
                                $user->save();
                return response()->json(['status'=>true], 200);
            }




        } catch (\Throwable $th) {
            return response()->json(['error'=>$th->getMessage()]);
        }
    }


public function getMyPlayedGames(Request $request)
{
    $user = $request->user();

    // Get all games user played
    $games = $user->playedGames()->with(['winners', 'losers'])->get();

    // Format the response
  
    $result = $games->map(function($game) use ($user) {
        
        return [
            'id' => $game->id,
            'name' => $game->name,
            'status' => $game->status,
            'odds'=>$game->odds,
            'creator'=>$game->creator->name,
            'stake'=>$game->stake,
            'result' => $game->winners->contains($user->id) 
                            ? 'won' 
                            : ($game->losers->contains($user->id) ? 'lost' : 'won'),
        ];
    });

    return response()->json($result);
}

    public function getAllGames(Request $request){
       
        $games = Game::where('status', 'open')->where("creator_id", '!=', $request->user()->id)->orderBy('stake', 'desc')->get();

        return response()->json(['games'=>$games, 'status'=>true]);
    }

    public function getGame($id){
        $game = Game::with('creator')->find($id);

        return response()->json(['status'=>true, 'game'=>$game], 200);
    }

    public function getMyGames(Request $request){
        $games = Game::where('creator_id', $request->user()->id)->get();
        return response()->json(['status'=>true, 'games'=>$games]);
    }

public function winLosersGame(Request $request){
     $game = Game::find($request->gameId);

     $game->losers_game_won = true;
     $game->save();

     return response()->json(['status'=>true], 200);
}
public function playLosersGame(Request $request){
     $game = Game::find($request->gameId);

     if($game->losers_game_won){
        return response()->json(['error'=>"This game has already been won", 'status'=>false], 200);
     }
   $userLost =  $game->losers()->where('user_id', $request->user()->id)->exists();

   if($userLost){
     $game->losers()->detach($request->user()->id);

     return response()->json(['status'=>true], 200);

   }else{
    return response()->json(['error'=>'You can not play this game', 'status'=>false], 200);
   }

}
    public function playGame(Request $request){
        $request->validate(['gameId'=>'required']);

        $game = Game::find($request->gameId);
$isAttached = $game->players()->where('user_id', $request->user()->id)->exists();

if ($isAttached) {
return response()->json(['error'=>'You have already played this game']);
}
if ($game->winners()->where('user_id', $request->user()->id)->exists() ||
    $game->losers()->where('user_id', $request->user()->id)->exists()) {
    return response()->json(['error' => 'You have already played this game']);
}

    $game->players()->attach($request->user()->id);

    if( $game->name == 'Lucky Number'){
        $numbers = explode(',', $game->number_result); // ["1","2","3","4","5"]


        if (in_array($request->choiceNumber, $numbers)) {
            // Reload fresh count to avoid stale data
            $winnersCount = $game->winners()->count();

          
            if ($game->number_of_winners && $winnersCount >= $game->number_of_winners) {
                return response()->json([
                    'status' => false,
                    'error' => 'Game is already closed.'
                ]);
            }
        
            // Attach winner
            $game->winners()->attach($request->user()->id);
        
            // Check count again in case two requests came in at the same time
            $newCount = $game->winners()->count();
        
            if ($newCount > $game->number_of_winners) {
                // Remove the extra winner (this user)
                $game->winners()->detach($request->user()->id);
        
                return response()->json([
                    'status' => false,
                    'error' => 'Game is already closed.'
                ]);
            }
        
            // If exactly $game->number_of_winners now → close the game
            if ($newCount == $game->number_of_winners) {
                $game->update(['status' => 'closed']);
            }
        
            return response()->json(['status' => true, 'success' => true]);
        }else{
               $game->losers()->syncWithoutDetaching([$request->user()->id => ['is_loser' => true]]);
        return response()->json(['status'=>true, 'success'=>false]);
    }
    }

    if($game->name == "Flip The Coin"){
        \Log::info($request->choice);
        if($game->coin_toss == $request->choice){
            $winnersCount = $game->winners()->count();
        
             if ($game->number_of_winners && $winnersCount >= $game->number_of_winners) {
                return response()->json([
                    'status' => false,
                    'error' => 'Game is already closed.'
                ]);
            }
        
            // Attach winner
            $game->winners()->attach($request->user()->id);
        
            // Check count again in case two requests came in at the same time
            $newCount = $game->winners()->count();
        
            if ($newCount > $game->number_of_winners) {
                // Remove the extra winner (this user)
                $game->winners()->detach($request->user()->id);
        
                return response()->json([
                    'status' => false,
                    'error' => 'Game is already closed.'
                ]);
            }
        
            // If exactly $game->number_of_winners now → close the game
            if ($newCount == $game->number_of_winners) {
                $game->update(['status' => 'closed']);
            }
        
            return response()->json(['status' => true, 'success' => true]);
        }else{
                    $game->losers()->syncWithoutDetaching([$request->user()->id => ['is_loser' => true]]);
            return response()->json(['status'=>true, 'success'=>false]);
        }
    }
    if($game->name =="Goal Challenge"){
       
          if($game->ball_direction == $request->direction){
            $winnersCount = $game->winners()->count();
        
             if ($game->number_of_winners && $winnersCount >= $game->number_of_winners) {
                return response()->json([
                    'status' => false,
                    'error' => 'Game is already closed.'
                ]);
            }
        
            // Attach winner
            $game->winners()->attach($request->user()->id);
        
            // Check count again in case two requests came in at the same time
            $newCount = $game->winners()->count();
        
            if ($newCount > $game->number_of_winners) {
                // Remove the extra winner (this user)
                $game->winners()->detach($request->user()->id);
        
                return response()->json([
                    'status' => false,
                    'error' => 'Game is already closed.'
                ]);
            }
        
            // If exactly $game->number_of_winners now → close the game
            if ($newCount == $game->number_of_winners) {
                $game->update(['status' => 'closed']);
            }
        
            return response()->json(['status' => true, 'success' => true]);
        }else{
                      $game->losers()->syncWithoutDetaching([$request->user()->id => ['is_loser' => true]]);
            return response()->json(['status'=>true, 'success'=>false]);
        }
    }
    if($game->name =="Dice Roll"){
       
          if($game->dice_result == $request->numberRolled){
            $winnersCount = $game->winners()->count();
        
             if ($game->number_of_winners && $winnersCount >= $game->number_of_winners) {
                return response()->json([
                    'status' => false,
                    'error' => 'Game is already closed.'
                ]);
            }
        
            // Attach winner
            $game->winners()->attach($request->user()->id);
        
            // Check count again in case two requests came in at the same time
            $newCount = $game->winners()->count();
        
            if ($newCount > $game->number_of_winners) {
                // Remove the extra winner (this user)
                $game->winners()->detach($request->user()->id);
        
                return response()->json([
                    'status' => false,
                    'error' => 'Game is already closed.'
                ]);
            }
        
            // If exactly $game->number_of_winners now → close the game
            if ($newCount == $game->number_of_winners) {
                $game->update(['status' => 'closed']);
            }
        
            return response()->json(['status' => true, 'success' => true]);
        }else{
                      $game->losers()->syncWithoutDetaching([$request->user()->id => ['is_loser' => true]]);
            return response()->json(['status'=>true, 'success'=>false]);
        }
    }

     if($game->name =="One Number Spin"){
       
          if($game->number_wheel_result == $request->numberWheeled){
            $winnersCount = $game->winners()->count();
        
             if ($game->number_of_winners && $winnersCount >= $game->number_of_winners) {
                return response()->json([
                    'status' => false,
                    'error' => 'Game is already closed.'
                ]);
            }
        
            // Attach winner
            $game->winners()->attach($request->user()->id);
        
            // Check count again in case two requests came in at the same time
            $newCount = $game->winners()->count();
        
            if ($newCount > $game->number_of_winners) {
                // Remove the extra winner (this user)
                $game->winners()->detach($request->user()->id);
        
                return response()->json([
                    'status' => false,
                    'error' => 'Game is already closed.'
                ]);
            }
        
            // If exactly $game->number_of_winners now → close the game
            if ($newCount == $game->number_of_winners) {
                $game->update(['status' => 'closed']);
            }
        
            return response()->json(['status' => true, 'success' => true]);
        }else{
                       $game->losers()->syncWithoutDetaching([$request->user()->id => ['is_loser' => true]]);
            return response()->json(['status'=>true, 'success'=>false]);
        }
    }

    if($game->name =="Mystery Box Game"){
       
          if($game->winning_box == $request->boxSelected){
            $winnersCount = $game->winners()->count();
        
             if ($game->number_of_winners && $winnersCount >= $game->number_of_winners) {
                return response()->json([
                    'status' => false,
                    'error' => 'Game is already closed.'
                ]);
            }
        
            // Attach winner
            $game->winners()->attach($request->user()->id);
        
            // Check count again in case two requests came in at the same time
            $newCount = $game->winners()->count();
        
            if ($newCount > $game->number_of_winners) {
                // Remove the extra winner (this user)
                $game->winners()->detach($request->user()->id);
        
                return response()->json([
                    'status' => false,
                    'error' => 'Game is already closed.'
                ]);
            }
        
            // If exactly $game->number_of_winners now → close the game
            if ($newCount == $game->number_of_winners) {
                $game->update(['status' => 'closed']);
            }
        
            return response()->json(['status' => true, 'success' => true]);
        }else{
                      $game->losers()->syncWithoutDetaching([$request->user()->id => ['is_loser' => true]]);
            return response()->json(['status'=>true, 'success'=>false]);
        }
    }
    if($game->name =="Color Roulette2"){
                  if($game->spin_wheel_result == $request->colorSpun){
                    $winnersCount = $game->winners()->count();
        
                     if ($game->number_of_winners && $winnersCount >= $game->number_of_winners) {
                        return response()->json([
                            'status' => false,
                            'error' => 'Game is already closed.'
                        ]);
                    }
                
                    // Attach winner
                    $game->winners()->attach($request->user()->id);
                
                    // Check count again in case two requests came in at the same time
                    $newCount = $game->winners()->count();
                
                    if ($newCount > $game->number_of_winners) {
                        // Remove the extra winner (this user)
                        $game->winners()->detach($request->user()->id);
                
                        return response()->json([
                            'status' => false,
                            'error' => 'Game is already closed.'
                        ]);
                    }
                
                    // If exactly $game->number_of_winners now → close the game
                    if ($newCount == $game->number_of_winners) {
                        $game->update(['status' => 'closed']);
                    }
                
                    return response()->json(['status' => true, 'success' => true]);
        }else{
                      $game->losers()->syncWithoutDetaching([$request->user()->id => ['is_loser' => true]]);
            return response()->json(['status'=>true, 'success'=>false]);
        }
    }

    if($game->name =="Color Roulette"){
                  if($game->spin_wheel_result == $request->colorSpun){
                    $winnersCount = $game->winners()->count();
        
                     if ($game->number_of_winners && $winnersCount >= $game->number_of_winners) {
                        return response()->json([
                            'status' => false,
                            'error' => 'Game is already closed.'
                        ]);
                    }
                
                    // Attach winner
                    $game->winners()->attach($request->user()->id);
                
                    // Check count again in case two requests came in at the same time
                    $newCount = $game->winners()->count();
                
                    if ($newCount > $game->number_of_winners) {
                        // Remove the extra winner (this user)
                        $game->winners()->detach($request->user()->id);
                
                        return response()->json([
                            'status' => false,
                            'error' => 'Game is already closed.'
                        ]);
                    }
                
                    // If exactly $game->number_of_winners now → close the game
                    if ($newCount == $game->number_of_winners) {
                        $game->update(['status' => 'closed']);
                    }
                
                    return response()->json(['status' => true, 'success' => true]);
        }else{
           $game->losers()->syncWithoutDetaching([$request->user()->id => ['is_loser' => true]]);
            return response()->json(['status'=>true, 'success'=>false]);
        }
    }

    if($game->name =="Spin The Bottle"){
                  if($game->spin_bottle == $request->direction){
                    $winnersCount = $game->winners()->count();
        
                     if ($game->number_of_winners && $winnersCount >= $game->number_of_winners) {
                        return response()->json([
                            'status' => false,
                            'error' => 'Game is already closed.'
                        ]);
                    }
                
                    // Attach winner
                    $game->winners()->attach($request->user()->id);
                
                    // Check count again in case two requests came in at the same time
                    $newCount = $game->winners()->count();
                
                    if ($newCount > $game->number_of_winners) {
                        // Remove the extra winner (this user)
                        $game->winners()->detach($request->user()->id);
                
                        return response()->json([
                            'status' => false,
                            'error' => 'Game is already closed.'
                        ]);
                    }
                
                    // If exactly $game->number_of_winners now → close the game
                    if ($newCount == $game->number_of_winners) {
                        $game->update(['status' => 'closed']);
                    }
                
                    return response()->json(['status' => true, 'success' => true]);
        }else{
           $game->losers()->syncWithoutDetaching([$request->user()->id => ['is_loser' => true]]);

            return response()->json(['status'=>true, 'success'=>false]);
        }
    }




    }

    public function getLosersGame(Request $request){
        $userId = $request->user()->id;
        $gamesUserLost = Game::whereHas('losers', function ($query) use ($userId) {
    $query->where('user_id', $userId);
})->get();




return response()->json(['games'=>$gamesUserLost], 200);
    }
}
