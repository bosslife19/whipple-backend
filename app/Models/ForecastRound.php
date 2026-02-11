<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ForecastRound extends Model
{
    protected $guarded = [];

    public function forecastMatch()
    {
        return $this->hasMany(ForecastMatch::class, 'forecast_round_id')->withDefault();
    }

    public function forecast()
    {
        return $this->hasMany(Forecast::class, 'forecast_round_id')->withDefault();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id')->withDefault();
    }
}
