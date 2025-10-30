<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkillGameMatch extends Model
{
    protected $guarded = [];

    public function players()
    {
        return $this->hasMany(SkillGameMatchPlayers::class, 'match_id');
    }

    public function game()
    {
        return $this->belongsTo(SkillGame::class, 'game_id')->withDefault();
    }
}
