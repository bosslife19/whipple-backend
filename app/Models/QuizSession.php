<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizSession extends Model
{
    protected $fillable = ['user_id', 'score'];
    public function answers()
    {
        return $this->hasMany(QuizAnswer::class);
    }
}
