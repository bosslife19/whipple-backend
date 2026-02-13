@extends('layouts.admin')

@section('content')
<div class="space-y-8">
    <div class="flex justify-end">
    <!-- Filter Form -->
        <form action="{{ route('admin.forecast') }}" method="GET" class="flex items-center bg-white border border-slate-200 rounded-lg overflow-hidden shadow-sm">
            <div class="px-3 py-2 border-r border-slate-200 flex items-center space-x-2">
                <span class="text-[10px] font-bold text-slate-400 uppercase">From</span>
                <input type="date" name="start_date" value="{{ request('start_date') }}" class="text-sm font-medium text-slate-700 outline-none">
            </div>
            <div class="px-3 py-2 border-r border-slate-200 flex items-center space-x-2">
                <span class="text-[10px] font-bold text-slate-400 uppercase">To</span>
                <input type="date" name="end_date" value="{{ request('end_date') }}" class="text-sm font-medium text-slate-700 outline-none">
            </div>
            <button type="submit" class="p-2 bg-slate-50 hover:bg-slate-100 text-slate-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </button>
            @if(request()->anyFilled(['start_date', 'end_date']))
                <a href="{{ route('admin.forecast') }}" class="p-2 text-red-500 hover:bg-red-50 transition border-l border-slate-200" title="Clear Filters">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </a>
            @endif
        </form>
    </div>
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Forecast Management</h2>
            <p class="text-slate-500">Manage matches, update scores, and track predictions.</p>
        </div>
        
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex items-center bg-white border border-slate-200 rounded-lg overflow-hidden shadow-sm">
                <a href="{{ route('admin.forecast.template') }}" title="Download Template" class="p-2 hover:bg-slate-50 text-slate-600 border-r border-slate-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                </a>
                <button onclick="openModal('uploadMatchesModal')" title="Upload Matches" class="p-2 hover:bg-slate-50 text-slate-600 border-r border-slate-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                </button>
                <a href="{{ route('admin.forecast.export', request()->all()) }}" title="Export to Excel" class="p-2 hover:bg-slate-50 text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </a>
            </div>

            <button onclick="openModal('createMatchModal')" class="bg-slate-900 text-white px-4 py-2 rounded-lg font-semibold hover:bg-slate-800 transition flex items-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span>Create Match</span>
            </button>
        </div>
    </div>


    @if(session('status'))
        <div class="p-4 bg-emerald-50 text-emerald-700 rounded-xl border border-emerald-100 flex items-center space-x-3">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <!-- Metric Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Card 1: Total Forecast Matches -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm shadow-slate-200/50">
            <h3 class="text-slate-500 text-sm font-semibold uppercase tracking-wider mb-4 text-center border-b border-slate-50 pb-2">Total Forecast Matches</h3>
            <div class="flex justify-around items-center h-16">
                <div class="text-center">
                    <p class="text-slate-400 text-xs font-semibold">DRAFT</p>
                    <p class="text-lg font-bold text-slate-900">{{ $metrics['all']['draft'] }}</p>
                </div>
                <div class="w-px h-8 bg-slate-100"></div>
                <div class="text-center">
                    <p class="text-blue-500 text-xs font-semibold">ACTIVE</p>
                    <p class="text-lg font-bold text-slate-900">{{ $metrics['all']['active'] }}</p>
                </div>
                <div class="w-px h-8 bg-slate-100"></div>
                <div class="text-center">
                    <p class="text-emerald-500 text-xs font-semibold">ENDED</p>
                    <p class="text-lg font-bold text-slate-900">{{ $metrics['all']['ended'] }}</p>
                </div>
            </div>
        </div>

        <!-- Card 2: General Matches -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm shadow-slate-200/50">
            <h3 class="text-slate-500 text-sm font-semibold uppercase tracking-wider mb-4 text-center border-b border-slate-50 pb-2">General Type</h3>
            <div class="flex justify-around items-center h-16">
                <div class="text-center">
                    <p class="text-slate-400 text-xs font-semibold">DRAFT</p>
                    <p class="text-lg font-bold text-slate-900">{{ $metrics['general']['draft'] }}</p>
                </div>
                <div class="w-px h-8 bg-slate-100"></div>
                <div class="text-center">
                    <p class="text-blue-500 text-xs font-semibold">ACTIVE</p>
                    <p class="text-lg font-bold text-slate-900">{{ $metrics['general']['active'] }}</p>
                </div>
                <div class="w-px h-8 bg-slate-100"></div>
                <div class="text-center">
                    <p class="text-emerald-500 text-xs font-semibold">ENDED</p>
                    <p class="text-lg font-bold text-slate-900">{{ $metrics['general']['ended'] }}</p>
                </div>
            </div>
        </div>

        <!-- Card 3: Specific Matches -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm shadow-slate-200/50">
            <h3 class="text-slate-500 text-sm font-semibold uppercase tracking-wider mb-4 text-center border-b border-slate-50 pb-2">Specific Type</h3>
            <div class="flex justify-around items-center h-16">
                <div class="text-center">
                    <p class="text-slate-400 text-xs font-semibold">DRAFT</p>
                    <p class="text-lg font-bold text-slate-900">{{ $metrics['specific']['draft'] }}</p>
                </div>
                <div class="w-px h-8 bg-slate-100"></div>
                <div class="text-center">
                    <p class="text-blue-500 text-xs font-semibold">ACTIVE</p>
                    <p class="text-lg font-bold text-slate-900">{{ $metrics['specific']['active'] }}</p>
                </div>
                <div class="w-px h-8 bg-slate-100"></div>
                <div class="text-center">
                    <p class="text-emerald-500 text-xs font-semibold">ENDED</p>
                    <p class="text-lg font-bold text-slate-900">{{ $metrics['specific']['ended'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Matches Table -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm shadow-slate-200/50 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50/50 text-slate-500 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-4 font-semibold">Match Details</th>
                        <th class="px-6 py-4 font-semibold text-center">Kickoff</th>
                        <th class="px-6 py-4 font-semibold text-center">Type</th>
                        <th class="px-6 py-4 font-semibold text-center">Status</th>
                        <th class="px-6 py-4 font-semibold text-center">Scores</th>
                        <th class="px-6 py-4 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($matches as $match)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-4">
                                    <div class="text-right">
                                        <p class="text-sm font-bold text-slate-900">{{ $match->team_name_a }}</p>
                                        <img src="{{ $match->team_logo_a }}" class="w-8 h-8 ml-auto object-contain" alt="">
                                    </div>
                                    <span class="text-slate-300 font-bold italic">VS</span>
                                    <div class="text-left">
                                        <p class="text-sm font-bold text-slate-900">{{ $match->team_name_b }}</p>
                                        <img src="{{ $match->team_logo_b }}" class="w-8 h-8 mr-auto object-contain" alt="">
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <p class="text-sm text-slate-700 font-medium">{{ \Carbon\Carbon::parse($match->kickoff_time)->format('M d, Y') }}</p>
                                <p class="text-xs text-slate-400">{{ \Carbon\Carbon::parse($match->kickoff_time)->format('H:i') }}</p>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2 py-1 rounded-md text-[10px] uppercase font-bold {{ $match->type === 'general' ? 'bg-purple-50 text-purple-600 border border-purple-100' : 'bg-cyan-50 text-cyan-600 border border-cyan-100' }}">
                                    {{ $match->type }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @php
                                    $statusColors = [
                                        'draft' => 'text-slate-400 bg-slate-50 border-slate-100',
                                        'active' => 'text-blue-500 bg-blue-50 border-blue-100',
                                        'ended' => 'text-emerald-500 bg-emerald-50 border-emerald-100',
                                    ];
                                @endphp
                                <span class="px-2 py-1 rounded-full text-[10px] uppercase font-bold border {{ $statusColors[$match->status] }}">
                                    {{ $match->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($match->status === 'ended')
                                    <span class="text-sm font-black text-slate-800">{{ $match->score_a }} - {{ $match->score_b }}</span>
                                @else
                                    <span class="text-xs text-slate-300">TBD</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button onclick="openEditModal({{ json_encode($match) }})" class="p-2 text-slate-400 hover:text-blue-600 transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-400 italic">No matches uploaded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Match Modal -->
<div id="createMatchModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden" onclick="event.stopPropagation()">
        <div class="px-8 py-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <h3 class="text-xl font-bold text-slate-900">Create New Match</h3>
            <button onclick="closeModal('createMatchModal')" class="text-slate-400 hover:text-slate-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l18 18"></path></svg>
            </button>
        </div>
        <form action="{{ route('admin.forecast.store') }}" method="POST" class="p-8 space-y-6">
            @csrf
            <div class="grid grid-cols-2 gap-6">
                <!-- Team A -->
                <div class="space-y-4">
                    <h4 class="font-bold text-sm text-blue-600 uppercase tracking-widest">Home Team (A)</h4>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">TEAM NAME</label>
                        <input type="text" name="team_name_a" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">LOGO URL</label>
                        <input type="text" name="team_logo_a" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" placeholder="https://...">
                    </div>
                </div>
                <!-- Team B -->
                <div class="space-y-4">
                    <h4 class="font-bold text-sm text-red-600 uppercase tracking-widest">Away Team (B)</h4>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">TEAM NAME</label>
                        <input type="text" name="team_name_b" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">LOGO URL</label>
                        <input type="text" name="team_logo_b" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" placeholder="https://...">
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-6 pt-4 border-t border-slate-50">
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">KICKOFF TIME</label>
                    <input type="datetime-local" name="kickoff_time" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">GAME TYPE</label>
                    <select name="type" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                        <option value="general">General</option>
                        <option value="specific">Specific</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">STATUS</label>
                    <select name="status" id="create_status" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                        <option value="draft">Draft</option>
                        <option value="active" selected>Active</option>
                        <option value="ended">Ended</option>
                    </select>
                </div>
            </div>

            <!-- Scores for Create (Hidden unless status is ended) -->
            <div id="createEndedFields" class="grid grid-cols-2 gap-6 pt-4 border-t border-slate-50 hidden">
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">FINAL SCORE A</label>
                    <input type="number" name="score_a" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">FINAL SCORE B</label>
                    <input type="number" name="score_b" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
            </div>

            <div class="flex justify-end pt-6">

                <button type="submit" class="bg-blue-600 text-white px-8 py-3 rounded-xl font-bold hover:bg-blue-700 shadow-lg shadow-blue-100 transition">
                    Create Match
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Match Modal -->
<div id="editMatchModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden">
        <div class="px-8 py-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <h3 class="text-xl font-bold text-slate-900">Edit Match Details</h3>
            <button onclick="closeModal('editMatchModal')" class="text-slate-400 hover:text-slate-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l18 18"></path></svg>
            </button>
        </div>
        <form id="editMatchForm" method="POST" class="p-8 space-y-6">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-2 gap-6">
                <!-- Team A -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">TEAM NAME A</label>
                        <input type="text" name="team_name_a" id="edit_team_name_a" required class="w-full px-4 py-2 border border-slate-200 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">LOGO URL A</label>
                        <input type="text" name="team_logo_a" id="edit_team_logo_a" required class="w-full px-4 py-2 border border-slate-200 rounded-lg">
                    </div>
                </div>
                <!-- Team B -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">TEAM NAME B</label>
                        <input type="text" name="team_name_b" id="edit_team_name_b" required class="w-full px-4 py-2 border border-slate-200 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">LOGO URL B</label>
                        <input type="text" name="team_logo_b" id="edit_team_logo_b" required class="w-full px-4 py-2 border border-slate-200 rounded-lg">
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-6 pt-4 border-t border-slate-50">
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">KICKOFF TIME</label>
                    <input type="datetime-local" name="kickoff_time" id="edit_kickoff_time" required class="w-full px-4 py-2 border border-slate-200 rounded-lg">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">GAME TYPE</label>
                    <select name="type" id="edit_type" required class="w-full px-4 py-2 border border-slate-200 rounded-lg">
                        <option value="general">General</option>
                        <option value="specific">Specific</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">STATUS</label>
                    <select name="status" id="edit_status" required class="w-full px-4 py-2 border border-slate-200 rounded-lg">
                        <option value="draft">Draft</option>
                        <option value="active">Active</option>
                        <option value="ended">Ended</option>
                    </select>
                </div>
            </div>

            <!-- Scores & Results (Visible only if status is ended) -->
            <div id="endedFields" class="grid grid-cols-2 gap-4 pt-4 border-t border-slate-50 hidden">
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">SCORE A</label>
                    <input type="number" name="score_a" id="edit_score_a" class="w-full px-4 py-2 border border-slate-200 rounded-lg">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">SCORE B</label>
                    <input type="number" name="score_b" id="edit_score_b" class="w-full px-4 py-2 border border-slate-200 rounded-lg">
                </div>
            </div>

            <div class="flex justify-end pt-6">

                <button type="submit" class="bg-indigo-600 text-white px-8 py-3 rounded-xl font-bold hover:bg-indigo-700 shadow-lg shadow-indigo-100 transition">
                    Update Match
                </button>
            </div>
        </form>
    </div>
</div>
<!-- Upload Matches Modal -->
<div id="uploadMatchesModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl overflow-hidden">
        <div class="px-8 py-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <h3 class="text-xl font-bold text-slate-900">Upload Matches</h3>
            <button onclick="closeModal('uploadMatchesModal')" class="text-slate-400 hover:text-slate-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form action="{{ route('admin.forecast.import') }}" method="POST" enctype="multipart/form-data" class="p-8 space-y-6" id="uploadForm">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-wider">Select CSV File</label>
                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-slate-300 border-dashed rounded-xl hover:border-blue-400 transition cursor-pointer relative" onclick="document.getElementById('file-upload').click()">
                    <div class="space-y-1 text-center">
                        <svg class="mx-auto h-12 w-12 text-slate-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div class="flex text-sm text-slate-600">
                            <label class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500">
                                <span id="file-name-display">Upload a file</span>
                                <input id="file-upload" name="file" type="file" class="sr-only" required accept=".csv" onchange="document.getElementById('file-name-display').innerText = this.files[0].name">
                            </label>
                            <p class="pl-1" id="drag-drop-text">or drag and drop</p>
                        </div>
                        <p class="text-xs text-slate-500">CSV up to 10MB</p>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-wider">Status Override (Optional)</label>
                <select name="status_override" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                    <option value="">Use file's status</option>
                    <option value="draft">Draft</option>
                    <option value="active">Active</option>
                    <option value="ended">Ended</option>
                </select>
                <p class="mt-2 text-[10px] text-slate-400">If selected, this status will be applied to all imported matches.</p>
            </div>

            <div class="flex justify-end pt-4">
                <button type="submit" class="w-full bg-blue-600 text-white px-8 py-3 rounded-xl font-bold hover:bg-blue-700 shadow-lg shadow-blue-100 transition">
                    Import Matches
                </button>
            </div>
        </form>
    </div>
</div>

<script>

function openModal(id) {
    document.getElementById(id).classList.remove('hidden');
    document.getElementById(id).classList.add('flex');
}

function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
    document.getElementById(id).classList.remove('flex');
}

function openEditModal(match) {
    const form = document.getElementById('editMatchForm');
    form.action = `/admin/forecast/${match.id}`;
    
    document.getElementById('edit_team_name_a').value = match.team_name_a;
    document.getElementById('edit_team_name_b').value = match.team_name_b;
    document.getElementById('edit_team_logo_a').value = match.team_logo_a;
    document.getElementById('edit_team_logo_b').value = match.team_logo_b;
    
    // Format date for datetime-local
    const date = new Date(match.kickoff_time);
    const formattedDate = date.toISOString().slice(0, 16);
    document.getElementById('edit_kickoff_time').value = formattedDate;
    
    document.getElementById('edit_type').value = match.type;
    document.getElementById('edit_status').value = match.status;
    
    if (match.status === 'ended') {
        document.getElementById('endedFields').classList.remove('hidden');
        document.getElementById('edit_score_a').value = match.score_a;
        document.getElementById('edit_score_b').value = match.score_b;
    } else {
        document.getElementById('endedFields').classList.add('hidden');
    }

    document.getElementById('edit_status').addEventListener('change', function() {
        if (this.value === 'ended') {
            document.getElementById('endedFields').classList.remove('hidden');
        } else {
            document.getElementById('endedFields').classList.add('hidden');
        }
    });
    
    openModal('editMatchModal');
}

// Add event listener for create status
document.getElementById('create_status').addEventListener('change', function() {
    if (this.value === 'ended') {
        document.getElementById('createEndedFields').classList.remove('hidden');
    } else {
        document.getElementById('createEndedFields').classList.add('hidden');
    }
});
</script>
@endsection

