<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhippleTournamentPlayer extends Model
{
    protected $table = 'whipple_tournament_players';

    protected $fillable = [
        'tournament_id', 'user_id', 'source', 'import_rank',
        'eliminated', 'eliminated_at', 'screen_share_ack',
    ];

    protected function casts(): array
    {
        return [
            'eliminated' => 'boolean',
            'screen_share_ack' => 'boolean',
            'eliminated_at' => 'datetime',
        ];
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(WhippleTournament::class, 'tournament_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
