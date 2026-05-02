<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhippleTournamentLobby extends Model
{
    protected $table = 'whipple_tournament_lobbies';

    protected $fillable = [
        'tournament_id', 'game_key', 'label', 'status',
        'countdown_seconds', 'countdown_started_at', 'started_at', 'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'countdown_started_at' => 'datetime',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(WhippleTournament::class, 'tournament_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(WhippleTournamentLobbyScore::class, 'lobby_id');
    }
}
