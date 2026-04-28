@extends('layouts.admin')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">User Management</h2>
            <p class="text-slate-500">View and manage all registered users.</p>
        </div>
        
        <form action="{{ route('admin.users.index') }}" method="GET" class="flex items-center space-x-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, email, phone..." 
                class="px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 min-w-[250px]">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-blue-700 transition">Search</button>
        </form>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50/50 text-slate-500 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-3 font-semibold">User</th>
                        <th class="px-6 py-3 font-semibold">Contact</th>
                        <th class="px-6 py-3 font-semibold text-center">Role</th>
                        <th class="px-6 py-3 font-semibold">Wallet</th>
                        <th class="px-6 py-3 font-semibold">Points</th>
                        <th class="px-6 py-3 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($users as $user)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 font-bold">
                                        {{ substr($user->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-slate-900">{{ $user->name }}</div>
                                        <div class="text-xs text-slate-500">Joined {{ $user->created_at->format('M Y') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-slate-600">{{ $user->email }}</div>
                                <div class="text-xs text-slate-500">{{ $user->phone }}</div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="text-xs font-bold px-2 py-1 rounded-full 
                                    @if($user->role === 'master') bg-purple-50 text-purple-600 
                                    @elseif($user->role === 'admin') bg-blue-50 text-blue-600 
                                    @else bg-slate-50 text-slate-600 @endif">
                                    {{ strtoupper($user->role) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-bold text-emerald-600 text-sm">
                                ₦{{ number_format($user->wallet_balance, 2) }}
                            </td>
                            <td class="px-6 py-4 font-bold text-amber-600 text-sm">
                                {{ number_format($user->whipple_point) }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('admin.users.show', $user->id) }}" class="inline-flex items-center px-3 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-lg transition">
                                    Manage
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500 italic">No users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($users->hasPages())
            <div class="px-6 py-4 border-t border-slate-50 bg-slate-50/30">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
