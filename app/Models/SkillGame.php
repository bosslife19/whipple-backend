<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkillGame extends Model
{
    protected $guarded = [];

    public function match()
    {
        return $this->hasMany(SkillGameMatch::class, 'game_id');
    }
}
