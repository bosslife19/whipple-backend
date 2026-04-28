<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhippleTournamentLobbyScore extends Model
{
    protected $table = 'whipple_tournament_lobby_scores';

    protected $fillable = ['lobby_id', 'user_id', 'score', 'rank', 'meta'];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function lobby(): BelongsTo
    {
        return $this->belongsTo(WhippleTournamentLobby::class, 'lobby_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
