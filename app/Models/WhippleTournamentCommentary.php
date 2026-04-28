<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhippleTournamentCommentary extends Model
{
    protected $table = 'whipple_tournament_commentary';

    protected $fillable = ['tournament_id', 'user_id', 'body', 'is_key_moment'];

    protected function casts(): array
    {
        return [
            'is_key_moment' => 'boolean',
        ];
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(WhippleTournament::class, 'tournament_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
