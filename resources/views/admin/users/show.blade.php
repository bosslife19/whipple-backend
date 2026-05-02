@extends('layouts.admin')

@section('content')
<div class="space-y-6">
    <div class="flex items-center space-x-4">
        <a href="{{ route('admin.users.index') }}" class="p-2 hover:bg-slate-100 rounded-full transition text-slate-500">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        </a>
        <div>
            <h2 class="text-2xl font-bold text-slate-900">{{ $user->name }}</h2>
            <p class="text-slate-500">UID: #{{ $user->id }} | Role: {{ strtoupper($user->role) }}</p>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-emerald-50 border-l-4 border-emerald-400 p-4 rounded-lg">
            <p class="text-emerald-700 font-medium">{{ session('success') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- User Info Card -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Financial Status</h3>
                    <div class="space-y-4">
                        <div class="bg-slate-50 rounded-xl p-4">
                            <p class="text-slate-500 text-xs font-medium">Wallet Balance</p>
                            <p class="text-2xl font-black text-emerald-600">₦{{ number_format($user->wallet_balance, 2) }}</p>
                        </div>
                        <div class="bg-slate-50 rounded-xl p-4">
                            <p class="text-slate-500 text-xs font-medium">Whipple Points</p>
                            <p class="text-2xl font-black text-amber-600">{{ number_format($user->whipple_point) }} <span class="text-xs">pts</span></p>
                        </div>
                    </div>
                </div>
                <div>
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Action Panel</h3>
                    <div class="space-y-4">
                        <!-- Update Balance -->
                        <form action="{{ route('admin.users.update-balance', $user->id) }}" method="POST" class="space-y-2">
                            @csrf
                            <div class="flex gap-2">
                                <input type="number" name="amount" placeholder="Amount" step="0.01" class="flex-1 px-3 py-2 border rounded-lg text-sm" required>
                                <select name="type" class="px-3 py-2 border rounded-lg text-sm bg-white" required>
                                    <option value="add">Add Fund</option>
                                    <option value="remove">Remove Fund</option>
                                </select>
                            </div>
                            <button type="submit" class="w-full bg-slate-900 text-white py-2 rounded-lg text-sm font-bold hover:bg-slate-800 transition">Update Wallet</button>
                        </form>

                        <hr class="border-slate-100">

                        <!-- Update Points -->
                        <form action="{{ route('admin.users.update-points', $user->id) }}" method="POST" class="space-y-2">
                            @csrf
                            <div class="flex gap-2">
                                <input type="number" name="amount" placeholder="Points" class="flex-1 px-3 py-2 border rounded-lg text-sm" required>
                                <select name="type" class="px-3 py-2 border rounded-lg text-sm bg-white" required>
                                    <option value="add">Add Pts</option>
                                    <option value="remove">Remove Pts</option>
                                </select>
                            </div>
                            <button type="submit" class="w-full bg-amber-600 text-white py-2 rounded-lg text-sm font-bold hover:bg-amber-700 transition">Update Points</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Transaction History -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-50">
                    <h3 class="font-bold text-slate-900">Recent Transactions</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50/50 text-slate-500 text-xs uppercase">
                            <tr>
                                <th class="px-6 py-3 font-semibold">Ref</th>
                                <th class="px-6 py-3 font-semibold">Type</th>
                                <th class="px-6 py-3 font-semibold">Amount</th>
                                <th class="px-6 py-3 font-semibold">Status</th>
                                <th class="px-6 py-3 font-semibold">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @foreach($transactions as $tx)
                                <tr class="text-sm">
                                    <td class="px-6 py-4 font-mono text-xs text-slate-500">{{ $tx->ref }}</td>
                                    <td class="px-6 py-4 capitalize font-medium text-slate-700">{{ $tx->type }}</td>
                                    <td class="px-6 py-4">
                                        <span class="font-bold @if(in_array($tx->type, ['win', 'deposit'])) text-emerald-600 @else text-slate-900 @endif">
                                            ₦{{ number_format($tx->amount, 2) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-xs font-bold px-2 py-0.5 rounded @if($tx->status === 'completed') bg-emerald-50 text-emerald-600 @else bg-slate-50 text-slate-500 @endif">
                                            {{ strtoupper($tx->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-slate-500 text-xs">{{ $tx->created_at->format('M d, H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4 bg-slate-50/30">
                    {{ $transactions->links() }}
                </div>
            </div>
        </div>

        <!-- Sidebar Info -->
        <div class="space-y-6">
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Game Statistics</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-slate-600 text-sm">Total Played</span>
                        <span class="font-bold text-slate-900">{{ number_format($stats['total_games']) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-emerald-600 text-sm font-medium">Games Won</span>
                        <span class="font-bold text-emerald-600">{{ number_format($stats['total_won']) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-red-600 text-sm font-medium">Games Lost</span>
                        <span class="font-bold text-red-600">{{ number_format($stats['total_lost']) }}</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Lifetime Activity</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-slate-600 text-sm">Gross Deposits</span>
                        <span class="font-bold text-slate-900">₦{{ number_format($stats['total_deposit'], 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-600 text-sm">Gross Withdrawals</span>
                        <span class="font-bold text-slate-900">₦{{ number_format($stats['total_withdrawal'], 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
