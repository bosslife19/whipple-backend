<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ForecastMatch extends Model
{
    protected $guarded = [];

    public function forecast()
    {
        return $this->hasMany(Forecast::class, 'match_id')->withDefault();
    }
}
