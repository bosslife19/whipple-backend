<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\LeaderboardSetting;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

$demoUsers = User::where('referral_code', 'demo')->get();
echo "Demo Users: " . $demoUsers->count() . "\n";

$setting = LeaderboardSetting::where('is_current', true)->first();
if ($setting) {
    $from = $setting->period_start->startOfDay();
    $to = $setting->period_end->endOfDay();
    echo "Current Week: $from to $to\n";

    foreach ($demoUsers->take(5) as $user) {
        $txCount = DB::table('transactions')->where('user_id', $user->id)->whereBetween('created_at', [$from, $to])->count();
        $quizCount = DB::table('quiz_sessions')->where('user_id', $user->id)->whereBetween('created_at', [$from, $to])->count();
        $skillCount = DB::table('skill_game_match_players')->where('user_id', $user->id)->whereBetween('created_at', [$from, $to])->count();
        echo "User {$user->id} ({$user->name}): TXs: $txCount, Quizzes: $quizCount, Skills: $skillCount\n";
    }
} else {
    echo "No active week found.\n";
}
