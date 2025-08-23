<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    // Game.php
protected $guarded = [];
public function creator()
{
    return $this->belongsTo(User::class, 'creator_id');
}

public function players()
{
    return $this->belongsToMany(User::class, 'game_user')
                ->withTimestamps();
                
}
public function winners()
{
    return $this->belongsToMany(User::class, 'game_user')
        ->withTimestamps()
        ->wherePivot('is_winner', true);
}


public function losers()
{
    return $this->belongsToMany(User::class, 'game_user')
        ->withTimestamps()
        ->wherePivot('is_loser', true);
}

public function votes(){
    return $this->hasMany(Vote::class, 'game_id');
}

}
