@extends('layouts.admin')

@section('content')
<div class="space-y-8">
    <div>
        <h2 class="text-2xl font-bold text-slate-900">Dashboard Overview</h2>
        <p class="text-slate-500">Real-time metrics and platform activity.</p>
    </div>

    <!-- Metric Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Users -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm shadow-slate-200/50">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-blue-50 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </div>
                <span class="text-xs font-semibold text-emerald-600 bg-emerald-50 px-2 py-1 rounded">Active</span>
            </div>
            <h3 class="text-slate-500 text-sm font-medium">Total Users</h3>
            <p class="text-2xl font-bold text-slate-900 mt-1">{{ number_format($metrics['total_users']) }}</p>
        </div>

        <!-- Total Volume -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm shadow-slate-200/50">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-emerald-50 rounded-lg">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <span class="text-xs font-semibold text-emerald-600 bg-emerald-50 px-2 py-1 rounded">Gross</span>
            </div>
            <h3 class="text-slate-500 text-sm font-medium">Total Volume</h3>
            <p class="text-2xl font-bold text-slate-900 mt-1">₦{{ number_format($metrics['total_volume'], 2) }}</p>
        </div>

        <!-- Points Earned -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm shadow-slate-200/50">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-amber-50 rounded-lg">
                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </div>
                <span class="text-xs font-semibold text-amber-600 bg-amber-50 px-2 py-1 rounded">Whipple</span>
            </div>
            <h3 class="text-slate-500 text-sm font-medium">Points Earned</h3>
            <p class="text-2xl font-bold text-slate-900 mt-1">{{ number_format($metrics['total_points_earned']) }} pts</p>
        </div>

        <!-- Games Today -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm shadow-slate-200/50">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-indigo-50 rounded-lg">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                </div>
                <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-1 rounded">Today</span>
            </div>
            <h3 class="text-slate-500 text-sm font-medium">Games Played</h3>
            <p class="text-2xl font-bold text-slate-900 mt-1">{{ number_format($metrics['total_games_today']) }}</p>
        </div>
    </div>

    <!-- Secondary Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Game Stats -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
            <h3 class="font-bold text-slate-900 mb-4">Games Played (Periodic)</h3>
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-slate-600">This Week</span>
                    <span class="font-bold text-slate-900">{{ number_format($metrics['total_games_week']) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-600">This Month</span>
                    <span class="font-bold text-slate-900">{{ number_format($metrics['total_games_month']) }}</span>
                </div>
            </div>
        </div>

        <!-- Deposits Status -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
            <h3 class="font-bold text-slate-900 mb-4">Deposits</h3>
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-emerald-600">Completed</span>
                    <span class="font-bold text-slate-900">₦{{ number_format($metrics['deposits']['completed'], 2) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-amber-600">Pending</span>
                    <span class="font-bold text-slate-900">₦{{ number_format($metrics['deposits']['pending'], 2) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-red-600">Failed</span>
                    <span class="font-bold text-slate-900">₦{{ number_format($metrics['deposits']['failed'], 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Withdrawals Status -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
            <h3 class="font-bold text-slate-900 mb-4">Withdrawals</h3>
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-emerald-600">Completed</span>
                    <span class="font-bold text-slate-900">₦{{ number_format($metrics['withdrawals']['completed'], 2) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-amber-600">Pending</span>
                    <span class="font-bold text-slate-900">₦{{ number_format($metrics['withdrawals']['pending'], 2) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-red-600">Failed</span>
                    <span class="font-bold text-slate-900">₦{{ number_format($metrics['withdrawals']['failed'], 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Users Table -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm shadow-slate-200/50 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-50 flex items-center justify-between">
            <h3 class="font-bold text-slate-900">Recently Joined Users</h3>
            <a href="{{ route('admin.users.index') }}" class="text-sm font-semibold text-blue-600 hover:text-blue-700">View All</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50/50 text-slate-500 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-3 font-semibold">User</th>
                        <th class="px-6 py-3 font-semibold">Email</th>
                        <th class="px-6 py-3 font-semibold">Wallet Balance</th>
                        <th class="px-6 py-3 font-semibold">Joined Date</th>
                        <th class="px-6 py-3 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($metrics['recent_users'] as $user)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 text-xs font-bold">
                                        {{ substr($user->name, 0, 1) }}
                                    </div>
                                    <span class="text-sm font-medium text-slate-900">{{ $user->name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600">{{ $user->email }}</td>
                            <td class="px-6 py-4">
                                <span class="text-sm font-bold text-emerald-600">₦{{ number_format($user->wallet_balance ?? 0, 2) }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-500">{{ $user->created_at->format('M d, Y') }}</td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('admin.users.show', $user->id) }}" class="text-blue-600 hover:text-blue-800 text-sm font-semibold">Details</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-500 text-sm italic">No users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
