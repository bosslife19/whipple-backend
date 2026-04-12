<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentRoundScore extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'finished_at' => 'datetime',
            'left_early' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(TournamentRound::class, 'tournament_round_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
