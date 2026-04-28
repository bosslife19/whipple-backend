@extends('layouts.admin')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Transaction Logs</h2>
            <p class="text-slate-500">Total Volume: <span class="font-bold text-slate-900">₦{{ number_format($total_volume, 2) }}</span></p>
        </div>
        
        <form action="{{ route('admin.transactions.index') }}" method="GET" class="flex flex-wrap items-center gap-2">
            <select name="type" class="px-3 py-2 border rounded-lg text-sm bg-white">
                <option value="">All Types</option>
                <option value="deposit" {{ request('type') == 'deposit' ? 'selected' : '' }}>Deposit</option>
                <option value="withdrawal" {{ request('type') == 'withdrawal' ? 'selected' : '' }}>Withdrawal</option>
                <option value="game" {{ request('type') == 'game' ? 'selected' : '' }}>Game</option>
                <option value="win" {{ request('type') == 'win' ? 'selected' : '' }}>Win</option>
            </select>
            <select name="status" class="px-3 py-2 border rounded-lg text-sm bg-white">
                <option value="">All Status</option>
                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
            </select>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Ref or User..." 
                class="px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <button type="submit" class="bg-slate-900 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-slate-800">Filter</button>
        </form>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50/50 text-slate-500 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-3 font-semibold">User</th>
                        <th class="px-6 py-3 font-semibold">Reference</th>
                        <th class="px-6 py-3 font-semibold">Type</th>
                        <th class="px-6 py-3 font-semibold">Amount</th>
                        <th class="px-6 py-3 font-semibold">Status</th>
                        <th class="px-6 py-3 font-semibold">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($transactions as $tx)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="px-6 py-4">
                                <a href="{{ route('admin.users.show', $tx->user_id) }}" class="text-sm font-bold text-blue-600 hover:underline">
                                    {{ $tx->user->name }}
                                </a>
                                <div class="text-xs text-slate-500">{{ $tx->user->email }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-mono text-xs text-slate-600">{{ $tx->ref }}</div>
                                @if($tx->description)
                                    <div class="text-[10px] text-slate-400 mt-1">{{ Str::limit($tx->description, 30) }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs font-bold uppercase tracking-tighter">{{ $tx->type }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-bold @if(in_array($tx->type, ['win', 'deposit'])) text-emerald-600 @else text-slate-900 @endif">
                                    ₦{{ number_format($tx->amount, 2) }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs font-bold px-2 py-1 rounded
                                    @if($tx->status === 'completed') bg-emerald-50 text-emerald-600 
                                    @elseif($tx->status === 'pending') bg-amber-50 text-amber-600 
                                    @else bg-red-50 text-red-600 @endif">
                                    {{ strtoupper($tx->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-500">
                                {{ $tx->created_at->format('M d, Y H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500 italic">No transactions found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 bg-slate-50/30">
            {{ $transactions->links() }}
        </div>
    </div>
</div>
@endsection
