<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaderboardSetting extends Model
{
    protected $fillable = ['period_start', 'period_end'];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
        ];
    }
}
