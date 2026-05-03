@extends('layouts.admin')

@section('content')
<div class="space-y-8">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Whipple Leaderboards</h2>
            <p class="text-slate-500">Most Frequent & Most Wins — top {{ \App\Services\LeaderboardService::TOP_DISPLAY }} (top {{ \App\Services\LeaderboardService::TOURNAMENT_CUTOFF }} highlighted when qualified).</p>
            <div class="flex items-center gap-4 mt-2">
                <p class="text-sm text-slate-600">Period: <span class="font-semibold">{{ $period_start }}</span> → <span class="font-semibold">{{ $period_end }}</span></p>
                @if($currentWeek)
                    <span class="px-2 py-0.5 rounded text-xs font-bold {{ $currentWeek->status === 'active' ? 'bg-emerald-100 text-emerald-700' : ($currentWeek->status === 'paused' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-700') }}">
                        {{ strtoupper($currentWeek->status) }}
                    </span>
                @endif
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <form action="{{ route('admin.leaderboard.pause') }}" method="POST">
                @csrf
                <button type="submit" class="px-4 py-2 rounded-lg font-semibold transition {{ ($currentWeek && $currentWeek->status === 'paused') ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-amber-600 hover:bg-amber-700' }} text-white">
                    {{ ($currentWeek && $currentWeek->status === 'paused') ? 'Resume Accumulation' : 'Pause Accumulation' }}
                </button>
            </form>
            
            @if($currentWeek)
            <button onclick="document.getElementById('edit-week-modal').classList.remove('hidden')" class="bg-slate-600 hover:bg-slate-700 text-white px-4 py-2 rounded-lg font-semibold transition">
                Edit Week
            </button>
            @endif

            <button onclick="document.getElementById('new-week-modal').classList.remove('hidden')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold transition">
                Create New Week
            </button>
        </div>
    </div>

    @if(session('status'))
        <div class="p-4 bg-emerald-50 text-emerald-700 rounded-xl border border-emerald-100">{{ session('status') }}</div>
    @endif
    @if(session('error'))
        <div class="p-4 bg-red-50 text-red-700 rounded-xl border border-red-100">{{ session('error') }}</div>
    @endif

    <div class="flex flex-col md:flex-row gap-8">
        <!-- Week Selector -->
        <div class="w-full md:w-64 space-y-4">
            <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
                <h3 class="font-bold text-slate-900 mb-2">View History</h3>
                <form action="{{ route('admin.leaderboard') }}" method="GET">
                    <select name="week_id" onchange="this.form.submit()" class="w-full border-slate-200 rounded-lg text-sm">
                        @foreach($weeks as $week)
                            <option value="{{ $week->id }}" {{ (request('week_id') == $week->id || (!$weekId && $week->is_current)) ? 'selected' : '' }}>
                                {{ $week->label }} ({{ $week->start_date->format('M d') }})
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>

            <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
                <h3 class="font-bold text-slate-900 mb-2">Virtual Players</h3>
                <form action="{{ route('admin.leaderboard.virtual-players') }}" method="POST" class="space-y-3">
                    @csrf
                    <div>
                        <label class="text-xs text-slate-500">Target Player Count</label>
                        <input type="number" name="count" value="10" min="0" max="50" class="w-full border-slate-200 rounded-lg text-sm">
                    </div>
                    <button type="submit" class="w-full bg-slate-900 hover:bg-slate-800 text-white py-2 rounded-lg text-sm font-semibold transition">
                        Generate Records
                    </button>
                </form>
            </div>
        </div>

        <!-- Leaderboards -->
        <div class="flex-1 grid grid-cols-1 gap-8">
            @foreach(['frequent' => 'Most Frequent', 'wins' => 'Most Wins'] as $key => $title)
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden h-fit">
                <div class="px-4 py-3 bg-slate-900 text-white font-semibold">{{ $title }}</div>
                <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 sticky top-0">
                            <tr>
                                <th class="text-left p-2">RANK</th>
                                <th class="text-left p-2">PLAYER</th>
                                <th class="text-right p-2">SCORE</th>
                                <th class="text-left p-2">STATUS</th>
                                <th class="text-left p-2">ALERT</th>
                                <th class="w-8"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $board = ($key === 'frequent' ? $frequent : $wins); @endphp
                            @foreach($board as $row)
                                @php 
                                    $q = $row['qualification'] ?? null;
                                    $reqs = $q['requirements'] ?? [];
                                    $topRankLimit = $currentWeek->top_rank ?? 32;
                                    $isTop = $row['rank'] <= $topRankLimit;
                                    
                                    // Calculate pts needed for top rank
                                    $thresholdScore = $board->where('rank', $topRankLimit)->first()[$key] ?? 0;
                                    $ptsNeeded = max(0, $thresholdScore - $row[$key]);
                                @endphp
                                <tr class="border-t hover:bg-slate-50 cursor-pointer {{ $isTop ? 'bg-amber-50' : '' }}" data-details-id="details-{{ $row['user_id'] }}-{{ $key }}" onclick="toggleDetails(this.dataset.detailsId)">
                                    <td class="p-2 font-bold {{ $isTop ? 'text-amber-600' : '' }}">#{{ $row['rank'] }}</td>
                                    <td class="p-2">
                                        <div class="font-bold {{ $isTop ? 'text-amber-900' : '' }}">{{ $row['name'] }}</div>
                                        @php $user = \App\Models\User::find($row['user_id']); @endphp
                                        @if($user && $user->referral_code === 'demo')
                                            <span class="text-[10px] bg-slate-100 text-slate-600 px-1 rounded">VIRTUAL</span>
                                        @endif
                                    </td>
                                    <td class="p-2 text-right font-bold">{{ number_format($row[$key], 1) }}</td>
                                    <td class="p-2">
                                        @if($row['qualified'] ?? false)
                                            <span class="bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full text-xs font-bold border border-emerald-200">✓ QUALIFIED</span>
                                        @else
                                            <span class="bg-red-100 text-red-700 px-2 py-0.5 rounded-full text-xs font-bold border border-red-200">✗ NOT QUALIFIED</span>
                                        @endif
                                    </td>
                                    <td class="p-2">
                                        @if(!($row['qualified'] ?? false))
                                            <span class="text-amber-600 flex items-center gap-1 text-xs">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                                You need more games to qualify
                                            </span>
                                        @endif

                                        @if(!$isTop && $row['rank'] > $topRankLimit)
                                            <div class="text-[10px] text-slate-500 font-semibold mt-0.5">
                                                Need {{ number_format($ptsNeeded + 0.1, 1) }} more pts for Top {{ $topRankLimit }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="p-2 text-slate-400">
                                        <svg class="w-4 h-4 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                    </td>
                                </tr>
                                <tr id="details-{{ $row['user_id'] }}-{{ $key }}" class="hidden bg-slate-900 text-white border-l-4 border-amber-500">
                                    <td colspan="6" class="p-4">
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                            @php
                                                $skillGames = $reqs['skill_games_min_3_each']['per_game'] ?? [];
                                                $quiz = $reqs['quiz_min_3_sessions'] ?? null;
                                                $forecastGen = $reqs['forecast_general_min_3'] ?? null;
                                                $forecastSpec = $reqs['forecast_specific_min_3'] ?? null;
                                            @endphp

                                            @foreach($skillGames as $sg)
                                            <div>
                                                <div class="flex justify-between text-xs mb-1">
                                                    <span class="text-slate-400">{{ ucwords(str_replace('_', ' ', $sg['key'])) }}</span>
                                                    <span class="{{ $sg['played'] >= 3 ? 'text-emerald-400' : 'text-slate-400' }} font-bold">{{ $sg['played'] }}/3</span>
                                                </div>
                                                <div class="h-1.5 w-full bg-slate-800 rounded-full overflow-hidden">
                                                    <div class="h-full bg-emerald-500 rounded-full transition-all leaderboard-progress-bar" data-width="{{ min(100, ($sg['played'] / 3) * 100) }}"></div>
                                                </div>
                                            </div>
                                            @endforeach

                                            @if($quiz)
                                            <div>
                                                <div class="flex justify-between text-xs mb-1">
                                                    <span class="text-slate-400">Quiz Sessions</span>
                                                    <span class="{{ $quiz['sessions'] >= 3 ? 'text-emerald-400' : 'text-slate-400' }} font-bold">{{ $quiz['sessions'] }}/3</span>
                                                </div>
                                                <div class="h-1.5 w-full bg-slate-800 rounded-full overflow-hidden">
                                                    <div class="h-full bg-emerald-500 rounded-full transition-all leaderboard-progress-bar" data-width="{{ min(100, ($quiz['sessions'] / 3) * 100) }}"></div>
                                                </div>
                                            </div>
                                            @endif

                                            @if($forecastGen)
                                            <div>
                                                <div class="flex justify-between text-xs mb-1">
                                                    <span class="text-slate-400">General Forecast</span>
                                                    <span class="{{ $forecastGen['met'] ? 'text-emerald-400' : 'text-slate-400' }} font-bold">{{ $forecastGen['count'] }}/3</span>
                                                </div>
                                                <div class="h-1.5 w-full bg-slate-800 rounded-full overflow-hidden">
                                                    <div class="h-full bg-emerald-500 rounded-full transition-all leaderboard-progress-bar" data-width="{{ min(100, ($forecastGen['count'] / 3) * 100) }}"></div>
                                                </div>
                                            </div>
                                            @endif

                                            @if($forecastSpec)
                                            <div>
                                                <div class="flex justify-between text-xs mb-1">
                                                    <span class="text-slate-400">Specific Forecast</span>
                                                    <span class="{{ $forecastSpec['met'] ? 'text-emerald-400' : 'text-slate-400' }} font-bold">{{ $forecastSpec['count'] }}/3</span>
                                                </div>
                                                <div class="h-1.5 w-full bg-slate-800 rounded-full overflow-hidden">
                                                    <div class="h-full bg-emerald-500 rounded-full transition-all leaderboard-progress-bar" data-width="{{ min(100, ($forecastSpec['count'] / 3) * 100) }}"></div>
                                                </div>
                                            </div>
                                            @endif
                                        </div>

                                        @if(!$row['qualified'])
                                        <div class="mt-4 border-t border-slate-800 pt-4">
                                            <div class="text-red-500 font-bold text-xs mb-1">Missing requirements:</div>
                                            <ul class="text-slate-400 text-xs list-disc list-inside">
                                                @foreach($skillGames as $sg)
                                                    @if($sg['played'] < 3)
                                                        <li>Play at least {{ 3 - $sg['played'] }} more {{ ucwords(str_replace('_', ' ', $sg['key'])) }} game(s)</li>
                                                    @endif
                                                @endforeach
                                                @if($quiz && $quiz['sessions'] < 3)
                                                    <li>Play at least {{ 3 - $quiz['sessions'] }} more Quiz Sessions</li>
                                                @endif
                                                @if($forecastGen && !$forecastGen['met'])
                                                    <li>Make at least {{ 3 - $forecastGen['count'] }} more General Forecast game(s)</li>
                                                @endif
                                                @if($forecastSpec && !$forecastSpec['met'])
                                                    <li>Make at least {{ 3 - $forecastSpec['count'] }} more Specific Forecast game(s)</li>
                                                @endif
                                                @if(($reqs['deposits_min_3']['count'] ?? 0) < 3)
                                                    <li>Make at least {{ 3 - ($reqs['deposits_min_3']['count'] ?? 0) }} more Deposits (minimum of {{ $reqs['deposits_min_3']['min_amount'] ?? 500 }} naira)</li>
                                                @endif
                                            </ul>
                                        </div>
                                        @endif
                                    </td>
                                </tr>
                             @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<!-- New Week Modal -->
<div id="new-week-modal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-bold text-lg text-slate-900">Create New Week</h3>
            <button onclick="document.getElementById('new-week-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form action="{{ route('admin.leaderboard.create-week') }}" method="POST" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Week Label</label>
                <input type="text" name="label" placeholder="e.g. Week 15 - Spring Tournament" required class="w-full border-slate-200 rounded-lg">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Start Date</label>
                    <input type="datetime-local" name="start_date" required class="w-full border-slate-200 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">End Date</label>
                    <input type="datetime-local" name="end_date" required class="w-full border-slate-200 rounded-lg">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Top Rank (Tournament Cutoff)</label>
                <input type="number" name="top_rank" value="32" min="1" max="100" required class="w-full border-slate-200 rounded-lg">
                <p class="text-[10px] text-slate-500 mt-1">Number of top players to highlight as qualified.</p>
            </div>
            <div class="pt-4">
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-xl font-bold transition">
                    Initialize Week
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Week Modal -->
@if($currentWeek)
<div id="edit-week-modal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-bold text-lg text-slate-900">Edit Week</h3>
            <button onclick="document.getElementById('edit-week-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form action="{{ route('admin.leaderboard.update-week') }}" method="POST" class="p-6 space-y-4">
            @csrf
            <input type="hidden" name="week_id" value="{{ $currentWeek->id }}">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Week Label</label>
                <input type="text" name="label" value="{{ $currentWeek->label }}" required class="w-full border-slate-200 rounded-lg">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Start Date</label>
                    <input type="datetime-local" name="start_date" value="{{ $currentWeek->start_date->format('Y-m-d\TH:i') }}" required class="w-full border-slate-200 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">End Date</label>
                    <input type="datetime-local" name="end_date" value="{{ $currentWeek->end_date->format('Y-m-d\TH:i') }}" required class="w-full border-slate-200 rounded-lg">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Top Rank (Tournament Cutoff)</label>
                <input type="number" name="top_rank" value="{{ $currentWeek->top_rank ?? 32 }}" min="1" max="100" required class="w-full border-slate-200 rounded-lg">
            </div>
            <div class="pt-4">
                <button type="submit" class="w-full bg-slate-900 hover:bg-slate-800 text-white py-3 rounded-xl font-bold transition">
                    Update Week Details
                </button>
            </div>
        </form>
    </div>
</div>
@endif

<script>
function initLeaderboardProgressBars() {
    document.querySelectorAll('.leaderboard-progress-bar').forEach((el) => {
        const width = Number(el.dataset.width || 0);
        el.style.width = `${Math.max(0, Math.min(100, width))}%`;
    });
}

function toggleDetails(id) {
    const el = document.getElementById(id);
    if (el.classList.contains('hidden')) {
        el.classList.remove('hidden');
    } else {
        el.classList.add('hidden');
    }
}

document.addEventListener('DOMContentLoaded', initLeaderboardProgressBars);
</script>
@endsection
