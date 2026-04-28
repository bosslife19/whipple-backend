<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhippleTournamentStreamSlot extends Model
{
    protected $table = 'whipple_tournament_stream_slots';

    protected $fillable = ['tournament_id', 'user_id', 'slot'];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(WhippleTournament::class, 'tournament_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
