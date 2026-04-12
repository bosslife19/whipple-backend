<?php

namespace App\Http\Controllers;

use App\Models\Forecast;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AdminStatsController extends Controller
{
    public function summary(): JsonResponse
    {
        $now = Carbon::now();

        $gamesDaily = DB::table('game_user')
            ->whereDate('created_at', $now->toDateString())
            ->count();

        $gamesWeekly = DB::table('game_user')
            ->whereBetween('created_at', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()])
            ->count();

        $gamesMonthly = DB::table('game_user')
            ->whereBetween('created_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])
            ->count();

        $skillDaily = DB::table('skill_game_matches')
            ->whereDate('created_at', $now->toDateString())
            ->where('status', 'finished')
            ->count();

        $skillWeekly = DB::table('skill_game_matches')
            ->whereBetween('created_at', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()])
            ->where('status', 'finished')
            ->count();

        $skillMonthly = DB::table('skill_game_matches')
            ->whereBetween('created_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])
            ->where('status', 'finished')
            ->count();

        $depositVolume = Transaction::query()
            ->where('type', 'deposit')
            ->where('status', 'completed')
            ->sum('amount');

        $withdrawVolume = Transaction::query()
            ->where('type', 'withdrawal')
            ->where('status', 'completed')
            ->sum('amount');

        return response()->json([
            'status' => true,
            'totals' => [
                'users' => User::query()->count(),
                'deposit_volume_completed' => (float) $depositVolume,
                'withdrawal_volume_completed' => (float) $withdrawVolume,
            ],
            'games_played' => [
                'casual_game_user_entries_daily' => $gamesDaily,
                'casual_game_user_entries_weekly' => $gamesWeekly,
                'casual_game_user_entries_monthly' => $gamesMonthly,
                'skill_matches_finished_daily' => $skillDaily,
                'skill_matches_finished_weekly' => $skillWeekly,
                'skill_matches_finished_monthly' => $skillMonthly,
            ],
            'forecasts' => [
                'pending' => Forecast::query()->where('status', 'pending')->count(),
                'scored' => Forecast::query()->where('status', 'scored')->count(),
            ],
            'transactions' => [
                'deposits' => Transaction::query()->where('type', 'deposit')->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status'),
                'withdrawals' => Transaction::query()->where('type', 'withdrawal')->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status'),
            ],
        ]);
    }
}
