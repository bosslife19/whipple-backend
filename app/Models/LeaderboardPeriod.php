<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class LeaderboardPeriod extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_current' => 'boolean',
        ];
    }

    public static function currentOrNew(): self
    {
        $row = static::query()->where('is_current', true)->orderByDesc('id')->first();
        if ($row) {
            return $row;
        }

        return static::query()->create([
            'label' => 'Week '.Carbon::now()->format('Y-m-d'),
            'starts_at' => Carbon::now()->startOfWeek(),
            'ends_at' => null,
            'is_current' => true,
        ]);
    }

    public function windowEnd(): Carbon
    {
        return $this->ends_at ?? Carbon::now();
    }
}
