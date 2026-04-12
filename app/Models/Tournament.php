<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Tournament extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(function (Tournament $t): void {
            if (empty($t->uuid)) {
                $t->uuid = (string) Str::uuid();
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(TournamentParticipant::class);
    }

    public function rounds(): HasMany
    {
        return $this->hasMany(TournamentRound::class);
    }

    public function commentaries(): HasMany
    {
        return $this->hasMany(TournamentCommentary::class);
    }

    public function screenShares(): HasMany
    {
        return $this->hasMany(TournamentScreenShare::class);
    }
}
