@extends('layouts.admin')

@section('content')
<div class="space-y-8">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Tournament Arena</h2>
            <p class="text-slate-500">Import from leaderboards, run lobbies, elimination, commentary, and stream slots.</p>
        </div>

        <div class="">
            @if(isset($allTournaments) && $allTournaments->count() > 0)
            <form action="{{ route('admin.tournament') }}" method="GET" class="flex items-center">
                <select name="id" onchange="this.form.submit()" class="border border-slate-200 rounded-xl px-3 py-3 text-sm bg-white shadow-sm focus:ring-blue-500 focus:border-blue-500 font-medium">
                    <option value="">-- Active / Draft --</option>
                    @foreach($allTournaments as $t)
                    <option value="{{ $t->id }}" {{ ($tournament && $tournament->id == $t->id) ? 'selected' : '' }}>
                        {{ $t->title }} ({{ ucfirst($t->status) }})
                    </option>
                    @endforeach
                </select>
            </form>
            @endif

            @if(!$tournament)
            <button onclick="document.getElementById('create-tournament-modal').classList.remove('hidden')" class="bg-blue-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-blue-700 transition shadow-lg shadow-blue-200 flex items-center gap-2 mt-4">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Create Tournament
            </button>
            @endif
        </div>

        @if(!$tournament)

        <!-- Create Tournament Modal -->
        <div id="create-tournament-modal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="font-bold text-lg text-slate-900">Initialize Tournament</h3>
                    <button onclick="document.getElementById('create-tournament-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <form action="{{ route('admin.tournament.store') }}" method="POST" class="p-6 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Tournament Title</label>
                        <input type="text" name="title" placeholder="Saturday Night Showdown" class="w-full border rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Start Date & Time</label>
                        <input type="datetime-local" name="start_at" required class="w-full border rounded-lg px-3 py-2">
                    </div>
                    <div class="pt-4">
                        <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-xl font-bold hover:bg-blue-700 transition">Begin Tournament Setup</button>
                    </div>
                </form>
            </div>
        </div>
        @endif
    </div>

    @if(session('status'))
    <div class="p-4 bg-emerald-50 text-emerald-700 rounded-xl border border-emerald-100">{{ session('status') }}</div>
    @endif

    @if(!$tournament)
    <p class="text-slate-600">No active or draft tournament. Create one to begin.</p>
    @else
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl border border-slate-100 p-6 shadow-sm">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="font-bold text-lg text-slate-900">{{ $tournament->title }}</h3>
                        <p class="text-sm text-slate-500">Scheduled for: <span class="font-semibold text-slate-700">{{ $tournament->start_at ? $tournament->start_at->format('M d, Y - H:i') : 'Not set' }}</span></p>
                    </div>
                    <span class="px-3 py-1 bg-slate-100 text-slate-700 rounded-full text-xs font-bold uppercase tracking-wider">{{ $tournament->status }}</span>
                </div>

                <div class="flex flex-wrap gap-3 pt-4 border-t border-slate-50">
                    <form action="{{ route('admin.tournament.activate') }}" method="POST">@csrf
                        <input type="hidden" name="tournament_id" value="{{ $tournament->id }}">
                        <button class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-bold transition">Mark active</button>
                    </form>
                    <form action="{{ route('admin.tournament.complete') }}" method="POST">@csrf
                        <input type="hidden" name="tournament_id" value="{{ $tournament->id }}">
                        <button class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-lg text-sm font-bold transition">Complete</button>
                    </form>
                    <button onclick="document.getElementById('edit-tournament-modal').classList.remove('hidden')" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-sm font-bold transition">Edit Details</button>
                    <form action="{{ route('admin.tournament.reset') }}" method="POST" onsubmit="return confirm('Reset entire tournament?');" class="ml-auto">@csrf
                        <input type="hidden" name="tournament_id" value="{{ $tournament->id }}">
                        <button class="px-4 py-2 bg-red-50 text-red-600 hover:bg-red-100 rounded-lg text-sm font-bold transition">Reset</button>
                    </form>
                </div>
            </div>

            <!-- Edit Tournament Modal -->
            <div id="edit-tournament-modal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="font-bold text-lg text-slate-900">Edit Tournament</h3>
                        <button onclick="document.getElementById('edit-tournament-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <form action="{{ route('admin.tournament.update') }}" method="POST" class="p-6 space-y-4">
                        @csrf
                        <input type="hidden" name="tournament_id" value="{{ $tournament->id }}">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Tournament Title</label>
                            <input type="text" name="title" value="{{ $tournament->title }}" required class="w-full border rounded-lg px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Start Date & Time</label>
                            <input type="datetime-local" name="start_at" value="{{ $tournament->start_at ? $tournament->start_at->format('Y-m-d\TH:i') : '' }}" required class="w-full border rounded-lg px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
                            <select name="status" class="w-full border rounded-lg px-3 py-2">
                                <option value="draft" {{ $tournament->status === 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="active" {{ $tournament->status === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="completed" {{ $tournament->status === 'completed' ? 'selected' : '' }}>Completed</option>
                            </select>
                        </div>
                        <div class="pt-4">
                            <button type="submit" class="w-full bg-slate-900 text-white py-3 rounded-xl font-bold hover:bg-slate-800 transition">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 p-6 shadow-sm space-y-4">
                <h4 class="font-bold">Import from leaderboard</h4>
                <form action="{{ route('admin.tournament.import') }}" method="POST" class="flex flex-wrap gap-2 items-end">
                    @csrf
                    <input type="hidden" name="tournament_id" value="{{ $tournament->id }}">
                    <div>
                        <label class="text-xs text-slate-500">Board Source</label>
                        <select name="source" class="border rounded-lg px-2 py-2 text-sm block">
                            <option value="wins">Most Wins</option>
                            <option value="frequent">Most Frequent</option>
                            <option value="both">Both Boards</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-slate-500">Count (max qualified)</label>
                        <input type="number" name="limit" value="32" min="1" max="100" class="border rounded-lg px-2 py-2 text-sm w-24">
                    </div>
                    <button class="bg-slate-900 text-white px-4 py-2 rounded-lg text-sm font-semibold">Import top N</button>
                </form>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <h4 class="font-bold">Add players manually</h4>
                    <div class="text-xs text-slate-500 bg-slate-100 px-2 py-1 rounded-md">Scroll to browse all users</div>
                </div>
                <form action="{{ route('admin.tournament.add-player') }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="tournament_id" value="{{ $tournament->id }}">

                    <div class="relative">
                        <input type="text" id="user-search-input" placeholder="Search by name or email..."
                            class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 bg-slate-50/50">
                        <div class="absolute left-3 top-2.5 text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                    </div>

                    <div id="user-checkbox-list" class="max-h-80 overflow-y-auto border border-slate-100 rounded-xl divide-y divide-slate-50 bg-slate-50/30">
                        @foreach($users as $user)
                        @php $isAdded = in_array($user->id, $existingPlayerIds); @endphp
                        <label class="user-item flex items-center p-3 hover:bg-white transition cursor-pointer group {{ $isAdded ? 'opacity-50 cursor-not-allowed' : '' }}"
                            data-search="{{ strtolower($user->name . ' ' . $user->email) }}">
                            <div class="relative flex items-center">
                                <input type="checkbox" name="user_ids[]" value="{{ $user->id }}"
                                    {{ $isAdded ? 'disabled checked' : '' }}
                                    class="w-5 h-5 rounded border-slate-300 text-blue-600 focus:ring-blue-500 transition {{ $isAdded ? 'bg-slate-200' : '' }}">
                            </div>
                            <div class="ml-4 flex-1">
                                <span class="block text-sm font-bold {{ $isAdded ? 'text-slate-400' : 'text-slate-900' }}">{{ $user->name }}</span>
                                <span class="block text-xs text-slate-500">{{ $user->email }}</span>
                            </div>
                            @if($isAdded)
                            <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-tighter bg-emerald-50 px-2 py-0.5 rounded border border-emerald-100">In Tournament</span>
                            @endif
                        </label>
                        @endforeach
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full bg-slate-900 text-white py-3 rounded-xl font-bold hover:bg-slate-800 transition shadow-lg shadow-slate-200">
                            Add Selected Players
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 p-6 shadow-sm">
                <h4 class="font-bold mb-2">Elimination (Overall Bottom N)</h4>
                <form action="{{ route('admin.tournament.eliminate') }}" method="POST" class="flex gap-2 items-end">
                    @csrf
                    <input type="hidden" name="tournament_id" value="{{ $tournament->id }}">
                    <input type="number" name="count" value="8" min="1" class="border rounded-lg px-2 py-2 w-24">
                    <button class="bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">Eliminate bottom N</button>
                </form>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 p-6 shadow-sm">
                <h4 class="font-bold mb-3">Players ({{ $tournament->players->count() }})</h4>
                <div class="overflow-x-auto max-h-64 overflow-y-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left border-b">
                                <th class="p-2">Rank</th>
                                <th class="p-2">User</th>
                                <th class="p-2">Points</th>
                                <th class="p-2">Eliminated</th>
                                <th class="p-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rankedPlayers as $p)
                            <tr class="border-t {{ $p->eliminated ? 'bg-red-50/50' : '' }}">
                                <td class="p-2 font-bold text-slate-700">{{ $p->current_rank }}</td>
                                <td class="p-2">
                                    <span class="{{ $p->eliminated ? 'line-through text-slate-400' : 'font-semibold text-slate-900' }}">
                                        {{ $p->user->name ?? $p->user_id }}
                                    </span>
                                    @if($p->eliminated)
                                    <span class="ml-2 text-[10px] font-bold text-red-600 bg-red-100 px-2 py-0.5 rounded border border-red-200 uppercase">Eliminated</span>
                                    @endif
                                </td>
                                <td class="p-2 font-bold">{{ $p->total_score ?? 0 }}</td>
                                <td class="p-2">{{ $p->eliminated ? 'Yes' : 'No' }}</td>
                                <td class="p-2">
                                    <form action="{{ route('admin.tournament.remove-player') }}" method="POST" onsubmit="return confirm('Remove player?');">@csrf
                                        <input type="hidden" name="tournament_id" value="{{ $tournament->id }}">
                                        <input type="hidden" name="user_id" value="{{ $p->user_id }}">
                                        <button class="text-red-600 text-xs font-semibold">Remove</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 p-6 shadow-sm space-y-4">
                <h4 class="font-bold">Game lobbies</h4>
                <form action="{{ route('admin.tournament.lobby') }}" method="POST" class="flex flex-wrap gap-2 items-end">
                    @csrf
                    <input type="hidden" name="tournament_id" value="{{ $tournament->id }}">
                    <select name="game_key" class="border rounded-lg px-2 py-2 text-sm">
                        <option value="quiz">Quiz</option>
                        <option value="tap_rush">Tap Rush</option>
                        <option value="math_clash">Math Clash</option>
                        <option value="color_switch">Color Switch</option>
                        <option value="defuse_x">Defuse-X</option>
                    </select>
                    <input type="text" name="label" placeholder="Label (optional)" class="border rounded-lg px-2 py-2 text-sm">
                    <button class="bg-slate-900 text-white px-3 py-2 rounded-lg text-sm font-semibold">Create lobby</button>
                </form>

                @foreach($tournament->lobbies->sortByDesc('created_at') as $lobby)
                <div class="border rounded-xl p-4 space-y-2">
                    <div class="flex flex-wrap justify-between gap-2">
                        <span class="font-semibold">{{ $lobby->label}} — <span class="text-slate-500">{{ucwords(str_replace('_', ' ', $lobby->game_key)) }}</span></span>
                        <div class="flex flex-wrap gap-2">
                            {{-- <form action="{{ route('admin.tournament.lobby-players') }}" method="POST">@csrf
                            <input type="hidden" name="lobby_id" value="{{ $lobby->id }}">
                            <button class="text-xs bg-slate-100 px-2 py-1 rounded">Add all active players</button>
                            </form> --}}
                            <form action="{{ route('admin.tournament.lobby-countdown') }}" method="POST">@csrf
                                <input type="hidden" name="lobby_id" value="{{ $lobby->id }}">
                                <button class="text-xs bg-amber-100 px-2 py-1 rounded">Start 20s countdown</button>
                            </form>
                            {{-- <form action="{{ route('admin.tournament.lobby-end') }}" method="POST">@csrf
                            <input type="hidden" name="lobby_id" value="{{ $lobby->id }}">
                            <button class="text-xs bg-emerald-100 px-2 py-1 rounded">End & rank</button>
                            </form> --}}
                        </div>
                    </div>
                    <p class="text-xs text-slate-500">Scores: {{ $lobby->scores->count() }}</p>
                    @if($lobby->scores->count() > 0)
                    <div class="mt-2 text-xs text-slate-600 bg-slate-50 rounded p-2 max-h-48 overflow-y-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-slate-200">
                                    <th class="pb-1 font-semibold">Rank</th>
                                    <th class="pb-1 font-semibold">Player</th>
                                    <th class="pb-1 font-semibold">Score</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($lobby->scores->sortBy('rank') as $score)
                                <tr class="border-b border-slate-100 last:border-0">
                                    <td class="py-1.5">{{ $score->rank }}</td>
                                    <td class="py-1.5">{{ $score->user->name ?? 'User #' . $score->user_id }}</td>
                                    <td class="py-1.5 font-bold text-slate-800">{{ $score->score }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>


        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-2xl border border-slate-100 p-6 shadow-sm space-y-3">
                <h4 class="font-bold">Live commentary</h4>
                <form action="{{ route('admin.tournament.commentary') }}" method="POST" class="space-y-2">
                    @csrf
                    <input type="hidden" name="tournament_id" value="{{ $tournament->id }}">
                    <textarea name="body" rows="4" class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="Commentary..." required></textarea>
                    <div class="flex gap-2">
                        <button type="submit" name="send_type" value="normal" class="flex-1 bg-blue-600 text-white py-2 rounded-lg font-semibold text-sm">Send (normal)</button>
                        <button type="submit" name="send_type" value="key_moment" class="flex-1 bg-amber-500 text-white py-2 rounded-lg font-semibold text-sm">Key moment (gold)</button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 p-6 shadow-sm space-y-3">
                <h4 class="font-bold">TikTok stream slots (2 players)</h4>
                <form action="{{ route('admin.tournament.stream-slot') }}" method="POST" class="space-y-2 text-sm">
                    @csrf
                    <input type="hidden" name="tournament_id" value="{{ $tournament->id }}">
                    <select name="slot" class="border rounded-lg px-2 py-2 w-full">
                        <option value="1">Slot 1</option>
                        <option value="2">Slot 2</option>
                    </select>
                    <input type="number" name="user_id" class="border rounded-lg px-2 py-2 w-full" placeholder="User ID" required>
                    <button class="w-full bg-slate-900 text-white py-2 rounded-lg font-semibold">Assign slot</button>
                </form>
                <ul class="text-xs text-slate-600 space-y-1">
                    @foreach($tournament->streamSlots as $s)
                    <li>Slot {{ $s->slot }}: {{ $s->user->name ?? $s->user_id }}</li>
                    @endforeach
                </ul>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 p-6 shadow-sm max-h-96 overflow-y-auto">
                <h4 class="font-bold mb-2">Commentary feed</h4>
                @foreach($tournament->commentary as $c)
                <div class="text-xs border-b border-slate-100 py-2 {{ $c->is_key_moment ? 'text-amber-700 font-semibold' : '' }}">
                    {{ $c->body }}
                    <span class="text-slate-400 block">{{ $c->created_at }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('user-search-input')?.addEventListener('input', function(e) {
        const query = e.target.value.toLowerCase().trim();
        const items = document.querySelectorAll('.user-item');

        items.forEach(item => {
            const searchableText = item.getAttribute('data-search');
            if (searchableText.includes(query)) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    });
</script>
@endpush