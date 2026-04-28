<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaderboardWeek extends Model
{
    protected $fillable = ['label', 'start_date', 'end_date', 'status', 'is_current', 'top_rank'];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_current' => 'boolean',
    ];

    public function pauses(): HasMany
    {
        return $this->hasMany(LeaderboardPause::class);
    }

    public static function current(): ?self
    {
        return self::where('is_current', true)->first();
    }
}
