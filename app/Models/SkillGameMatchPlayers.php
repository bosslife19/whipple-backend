<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkillGameMatchPlayers extends Model
{
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id')->withDefault();
    }

    public function match()
    {
        return $this->belongsTo(SkillGameMatch::class, 'match_id')->withDefault();
    }
}
