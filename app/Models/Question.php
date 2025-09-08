<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = ['question', 'options', 'correct'];
    protected $casts = ['options' => 'array'];
}
