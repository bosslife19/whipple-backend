@extends('layouts.admin')

@section('title', 'Analytics')

@section('content')
@php
    $t = $stats['totals'];
    $g = $stats['games_played'];
    $f = $stats['forecasts'];
    $txd = $stats['transactions']['deposits'] ?? collect();
    $txw = $stats['transactions']['withdrawals'] ?? collect();
@endphp
<div class="space-y-8">
    <div>
        <h2 class="text-2xl font-bold text-slate-900">Analytics</h2>
        <p class="text-slate-500">Volumes, activity, and transaction breakdown.</p>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
            <p class="text-sm text-slate-500">Total users</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($t['users']) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
            <p class="text-sm text-slate-500">Deposit volume (completed)</p>
            <p class="mt-1 text-2xl font-bold text-emerald-700">₦{{ number_format($t['deposit_volume_completed'], 2) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
            <p class="text-sm text-slate-500">Withdrawal volume (completed)</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">₦{{ number_format($t['withdrawal_volume_completed'], 2) }}</p>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
        <h3 class="font-bold text-slate-900">Games played</h3>
        <dl class="mt-4 grid gap-3 text-sm md:grid-cols-2">
            <div class="flex justify-between border-b border-slate-50 py-2"><dt>Casual (game_user) — today</dt><dd class="font-semibold">{{ number_format($g['casual_game_user_entries_daily']) }}</dd></div>
            <div class="flex justify-between border-b border-slate-50 py-2"><dt>Casual — this week</dt><dd class="font-semibold">{{ number_format($g['casual_game_user_entries_weekly']) }}</dd></div>
            <div class="flex justify-between border-b border-slate-50 py-2"><dt>Casual — this month</dt><dd class="font-semibold">{{ number_format($g['casual_game_user_entries_monthly']) }}</dd></div>
            <div class="flex justify-between border-b border-slate-50 py-2"><dt>Skill matches finished — today</dt><dd class="font-semibold">{{ number_format($g['skill_matches_finished_daily']) }}</dd></div>
            <div class="flex justify-between border-b border-slate-50 py-2"><dt>Skill — this week</dt><dd class="font-semibold">{{ number_format($g['skill_matches_finished_weekly']) }}</dd></div>
            <div class="flex justify-between border-b border-slate-50 py-2"><dt>Skill — this month</dt><dd class="font-semibold">{{ number_format($g['skill_matches_finished_monthly']) }}</dd></div>
        </dl>
    </div>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
            <h3 class="font-bold text-slate-900">Forecasts</h3>
            <p class="mt-2 text-sm text-slate-600">Pending: <strong>{{ $f['pending'] }}</strong></p>
            <p class="text-sm text-slate-600">Scored: <strong>{{ $f['scored'] }}</strong></p>
        </div>
        <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
            <h3 class="font-bold text-slate-900">Transactions by status</h3>
            <p class="mt-2 text-xs font-semibold uppercase text-slate-400">Deposits</p>
            <ul class="mt-1 text-sm">
                @forelse($txd as $status => $count)
                    <li>{{ $status }}: {{ $count }}</li>
                @empty
                    <li class="text-slate-400">No data</li>
                @endforelse
            </ul>
            <p class="mt-3 text-xs font-semibold uppercase text-slate-400">Withdrawals</p>
            <ul class="mt-1 text-sm">
                @forelse($txw as $status => $count)
                    <li>{{ $status }}: {{ $count }}</li>
                @empty
                    <li class="text-slate-400">No data</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
