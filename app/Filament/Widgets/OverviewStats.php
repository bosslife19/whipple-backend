<?php

namespace App\Filament\Widgets;

use App\Models\Forecast;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class OverviewStats extends BaseWidget
{
    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $now = Carbon::now();

        $gamesDaily = DB::table('game_user')
            ->whereDate('created_at', $now->toDateString())
            ->count();

        $skillDaily = DB::table('skill_game_matches')
            ->whereDate('created_at', $now->toDateString())
            ->where('status', 'finished')
            ->count();

        $depositVolume = Transaction::query()
            ->where('type', 'deposit')
            ->where('status', 'completed')
            ->sum('amount');

        return [
            Stat::make('Total users', (string) User::query()->count())
                ->description('Registered accounts')
                ->color('success'),
            Stat::make('Deposit volume (completed)', number_format((float) $depositVolume, 2))
                ->description('All time')
                ->color('info'),
            Stat::make('Games played today', (string) ($gamesDaily + $skillDaily))
                ->description('Casual pool + skill matches finished')
                ->color('warning'),
            Stat::make('Forecasts pending', (string) Forecast::query()->where('status', 'pending')->count())
                ->description('Awaiting results')
                ->color('gray'),
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->hasAdminPermission('analytics') ?? false;
    }
}
