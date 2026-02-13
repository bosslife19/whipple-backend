<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Forecast extends Model
{
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id')->withDefault();
    }

    public function match()
    {
        return $this->belongsTo(ForecastMatch::class, 'match_id')->withDefault();
    }

    public function forecastRound()
    {
        return $this->belongsTo(ForecastRound::class, 'forecast_round_id')->withDefault();
    }
}
