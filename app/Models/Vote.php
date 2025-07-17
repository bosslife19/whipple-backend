<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vote extends Model
{
    protected $guarded = [];

    public function game(){
        return $this->belongsTo(Game::class);
    }

    public function player(){
        return $this->belongsTo(User::class);
    }
}
