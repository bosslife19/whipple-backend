<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Forecast;
use App\Models\ForecastRound;
use App\Models\LeaderboardPause;
use App\Models\LeaderboardWeek;
use App\Models\QuizAnswer;
use App\Models\QuizSession;
use App\Models\SkillGame;
use App\Models\SkillGameMatch;
use App\Models\SkillGameMatchPlayers;
use App\Models\Transaction;
use App\Models\User;
use App\Services\LeaderboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LeaderboardAdminController extends Controller
{
    public function __construct(
        protected LeaderboardService $leaderboard
    ) {}

    public function index(Request $request)
    {
        $weekId = $request->get('week_id');
        $weeks = $this->leaderboard->getHistoricalWeeks();
        
        $currentWeek = $weekId ? LeaderboardWeek::find($weekId) : LeaderboardWeek::current();
        
        $raw = $this->leaderboard->computeScoresForAllUsers($weekId);
        $hydrated = $this->leaderboard->hydrateUserRows($raw, $weekId);
        
        $frequent = $this->leaderboard->buildBoard($hydrated, 'frequent')->take(LeaderboardService::TOP_DISPLAY);
        $wins = $this->leaderboard->buildBoard($hydrated, 'wins')->take(LeaderboardService::TOP_DISPLAY);
        
        $periodStart = $currentWeek ? $currentWeek->start_date : Carbon::now()->startOfWeek();
        $periodEnd = $currentWeek ? $currentWeek->end_date : Carbon::now()->endOfWeek();

        return view('admin.leaderboard', [
            'weekId' => $weekId,
            'title' => 'Leaderboards',
            'period_start' => $periodStart->toDateTimeString(),
            'period_end' => $periodEnd->toDateTimeString(),
            'frequent' => $frequent,
            'wins' => $wins,
            'weeks' => $weeks,
            'currentWeek' => $currentWeek,
        ]);
    }

    public function createWeek(Request $request)
    {
        $request->validate([
            'label' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'top_rank' => 'required|integer|min:1|max:100',
        ]);

        // Deactivate current weeks
        LeaderboardWeek::where('is_current', true)->update(['is_current' => false]);

        LeaderboardWeek::create([
            'label' => $request->label,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'top_rank' => $request->top_rank,
            'status' => 'active',
            'is_current' => true,
        ]);

        return back()->with('status', 'New leaderboard week initialized.');
    }

    public function updateWeek(Request $request)
    {
        $request->validate([
            'week_id' => 'required|exists:leaderboard_weeks,id',
            'label' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'top_rank' => 'required|integer|min:1|max:100',
        ]);

        $week = LeaderboardWeek::findOrFail($request->week_id);
        $week->update([
            'label' => $request->label,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'top_rank' => $request->top_rank,
        ]);

        return back()->with('status', 'Leaderboard week updated.');
    }

    public function togglePause(Request $request)
    {
        $week = LeaderboardWeek::current();
        if (!$week) return back()->with('error', 'No current week found.');

        if ($week->status === 'active') {
            $week->update(['status' => 'paused']);
            LeaderboardPause::create([
                'leaderboard_week_id' => $week->id,
                'paused_at' => Carbon::now(),
            ]);
            return back()->with('status', 'Leaderboard accumulation paused.');
        } elseif ($week->status === 'paused') {
            $week->update(['status' => 'active']);
            $pause = $week->pauses()->whereNull('resumed_at')->latest()->first();
            if ($pause) {
                $pause->update(['resumed_at' => Carbon::now()]);
            }
            return back()->with('status', 'Leaderboard accumulation resumed.');
        }

        return back()->with('error', 'Week is already completed.');
    }

    public function generateVirtualPlayers(Request $request)
    {
        $request->validate([
            'count' => 'required|integer|min:0|max:50',
        ]);

        $count = (int) $request->count;

        $firstNames = [
            'James', 'Mary', 'Robert', 'Patricia', 'John', 'Jennifer', 'Michael', 'Linda', 'David', 'Elizabeth',
            'Chukwudi', 'Oluchi', 'Kofi', 'Zuri', 'Abebe', 'Fatima', 'Kwame', 'Ama', 'Tunde', 'Nneka',
            'Li', 'Wei', 'Arjun', 'Ananya', 'Yuki', 'Mei', 'Chen', 'Sanya', 'Kenji', 'Sakura',
            'Alejandro', 'Sofia', 'Mateo', 'Isabella', 'Miguel', 'Lucia', 'Juan', 'Maria', 'Carlos', 'Elena',
            'Jean', 'Marie', 'Hans', 'Greta', 'Giovanni', 'Elsa', 'Pierre', 'Sophie', 'Marco', 'Francesca'
        ];
        
        $lastNames = [
            'Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Miller', 'Davis', 'Wilson',
            'Okonkwo', 'Mensah', 'Adeyemi', 'Kenyatta', 'Toure', 'Diallo', 'Obi', 'Sow',
            'Zhang', 'Wang', 'Sharma', 'Patel', 'Sato', 'Suzuki', 'Nguyen', 'Kim', 'Gupta', 'Tan',
            'Garcia', 'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Gonzalez', 'Perez', 'Sanchez',
            'Dubois', 'Müller', 'Schmidt', 'Rossi', 'Bianchi', 'Lefebvre', 'Moreau', 'Ferrari', 'Gruber'
        ];

        $currentDemoCount = User::where('referral_code', 'demo')->count();
        
        // Create new ones if needed
        for ($i = $currentDemoCount + 1; $i <= $count; $i++) {
            $name = $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];
            User::create([
                'name' => $name,
                'email' => 'virtual_' . Str::random(8) . '@whipple.com',
                'phone' => '+1' . rand(1000000, 9999999),
                'password' => bcrypt('password'),
                'referral_code' => 'demo',
                'wallet_balance' => rand(1000, 5000),
                'whipple_point' => rand(0, 100),
            ]);
        }

        // Update names of ALL demo users to be realistic (even if they existed)
        $demoUsers = User::where('referral_code', 'demo')->get();
        foreach ($demoUsers as $u) {
            $u->update([
                'name' => $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)]
            ]);
        }

        $week = LeaderboardWeek::current();
        if (!$week) return back()->with('error', 'No active week found.');

        if ($count == 0) {
            $this->clearActivityForUser($week->id);
            return back()->with('status', 'Synced virtual players. Total 0 active this week.');
        }

        $from = $week->start_date;
        $to = $week->end_date->isFuture() ? Carbon::now() : $week->end_date;

        $this->clearActivityForUser($week->id);
        $toUpdate = $demoUsers->take($count);
        
        foreach ($toUpdate as $user) {
            $this->generateActivityForUser($user, $week->id, $from, $to);
        }

        return back()->with('status', "Synced virtual players. Total $count active this week.");
    }

    private function clearActivityForUser(int $weekId)
    {
                
        SkillGameMatchPlayers::where('user_type', 'virtual')
            ->where('week_id', $weekId)
            ->delete();
        
        SkillGameMatch::where('user_type', 'virtual')
            ->where('week_id', $weekId)
            ->delete();

        QuizAnswer::where('user_type', 'virtual')
            ->where('week_id', $weekId)
            ->delete();
        QuizSession::where('user_type', 'virtual')
            ->where('week_id', $weekId)
            ->delete();

        Forecast::where('user_type', 'virtual')
            ->where('week_id', $weekId)
            ->delete();
            
        ForecastRound::where('user_type', 'virtual')
            ->where('week_id', $weekId)
            ->delete();

        Transaction::where('user_type', 'virtual')
            ->where('week_id', $weekId)
            ->delete();
    }

    private function generateActivityForUser(User $user, int $weekId, Carbon $from, Carbon $to)
    {
        // Skill Games
        $games = SkillGame::all();
        foreach ($games as $game) {
            $plays = rand(2, 5); // Some might not qualify (need 3)
            for ($i = 0; $i < $plays; $i++) {
                $matchTime = $from->copy()->addSeconds(rand(0, $to->diffInSeconds($from)));
                $match = SkillGameMatch::create([
                    'game_id' => $game->id,
                    'week_id' => $weekId,
                    'user_type' => 'virtual',
                    'status' => 'finished',
                    'pot_amount' => $game->stake * 2,
                    'created_at' => $matchTime,
                    'updated_at' => $matchTime,
                    'finished_at' => $matchTime->copy()->addMinutes(2),
                ]);

                $won = rand(0, 1);
                SkillGameMatchPlayers::create([
                    'match_id' => $match->id,
                    'user_id' => $user->id,
                    'week_id' => $weekId,
                    'user_type' => 'virtual',
                    'is_demo' => true,
                    'status' => 'finished',
                    'rank' => $won ? 1 : 2,
                    'score' => rand(100, 500),
                    'created_at' => $matchTime,
                ]);
            }
        }

        // Quizzes
        $quizPlays = rand(2, 5);
        for ($i = 0; $i < $quizPlays; $i++) {
            $quizTime = $from->copy()->addSeconds(rand(0, $to->diffInSeconds($from)));
            $session = QuizSession::create([
                'user_id' => $user->id,
                'week_id' => $weekId,
                'user_type' => 'virtual',
                'status' => 'completed',
                'created_at' => $quizTime,
            ]);

            $answers = rand(5, 10);
            for ($j = 0; $j < $answers; $j++) {
                QuizAnswer::create([
                    'quiz_session_id' => $session->id,
                    'week_id' => $weekId,
                    'user_type' => 'virtual',                    
                    'question_id' => 1, // assume id 1 exists or don't care
                    'is_correct' => rand(0, 1),
                    'created_at' => $quizTime,
                ]);
            }
        }

        // Forecasts
        $types = ['general', 'specific'];
        foreach ($types as $type) {
            $fCount = rand(2, 5);
            $fTime = $from->copy()->addSeconds(rand(0, $to->diffInSeconds($from)));
            
            $forecastRound = ForecastRound::create([
                'label' => 'Virtual Round ' . Str::random(5),
                'user_id' => $user->id,
                'week_id' => $weekId,
                'user_type' => 'virtual',
                'type' => $type,
                'status' => 'closed',
                'winnings' => rand(0, 100),
                'created_at' => $fTime,
            ]);

            for ($i = 0; $i < $fCount; $i++) {
                $outcomeType = rand(0, 2);
                $cA = 'Win'; $cB = 'Loss';
                if ($outcomeType === 1) {
                    $cA = 'Loss'; $cB = 'Win';
                } elseif ($outcomeType === 2) {
                    $cA = 'Draw'; $cB = 'Draw';
                }

                Forecast::create([
                    'user_id' => $user->id,
                    'forecast_round_id' => $forecastRound->id,
                    'week_id' => $weekId,
                    'user_type' => 'virtual',
                    'type' => $type,
                    'match_id' => 1,
                    'choice_a' => $cA,
                    'choice_b' => $cB,
                    'score_a' => rand(0, 5),
                    'score_b' => rand(0, 5),
                    'is_correct' => rand(0, 1),
                    'status' => 'scored',
                    'created_at' => $fTime,
                ]);
            }
        }

        // Deposits
        $tCount = rand(2, 5);
        for ($i = 0; $i < $tCount; $i++) {
            $tTime = $from->copy()->addSeconds(rand(0, $to->diffInSeconds($from)));
            Transaction::create([
                'user_id' => $user->id,
                'week_id' => $weekId,
                'user_type' => 'virtual',
                'amount' => rand(100, 1000),
                'type' => 'deposit',
                'status' => 'completed',
                'created_at' => $tTime,
            ]);
        }
    }

    public function reset(Request $request)
    {
        $this->leaderboard->resetWeeklyPeriod();
        return back()->with('status', 'Leaderboard period reset. A new weekly window is now active.');
    }
}
