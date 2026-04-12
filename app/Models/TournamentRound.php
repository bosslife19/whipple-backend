<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TournamentRound extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'countdown_ends_at' => 'datetime',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(TournamentRoundScore::class, 'tournament_round_id');
    }
}
