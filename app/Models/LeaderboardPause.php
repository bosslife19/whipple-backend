<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaderboardPause extends Model
{
    protected $fillable = ['leaderboard_week_id', 'paused_at', 'resumed_at'];

    protected $casts = [
        'paused_at' => 'datetime',
        'resumed_at' => 'datetime',
    ];

    public function week(): BelongsTo
    {
        return $this->belongsTo(LeaderboardWeek::class, 'leaderboard_week_id');
    }
}
