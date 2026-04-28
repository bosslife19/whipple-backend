<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhippleTournament extends Model
{
    protected $table = 'whipple_tournaments';

    protected $fillable = ['title', 'status', 'start_at'];

    protected $casts = [
        'start_at' => 'datetime',
    ];

    public function players(): HasMany
    {
        return $this->hasMany(WhippleTournamentPlayer::class, 'tournament_id');
    }

    public function lobbies(): HasMany
    {
        return $this->hasMany(WhippleTournamentLobby::class, 'tournament_id');
    }

    public function commentary(): HasMany
    {
        return $this->hasMany(WhippleTournamentCommentary::class, 'tournament_id')->latest();
    }

    public function streamSlots(): HasMany
    {
        return $this->hasMany(WhippleTournamentStreamSlot::class, 'tournament_id');
    }
}
