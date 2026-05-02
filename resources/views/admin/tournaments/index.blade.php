@extends('layouts.admin')

@section('title', 'Tournaments')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Tournaments</h2>
            <p class="text-slate-500">Create a tournament, then manage rounds and players.</p>
        </div>
        <a href="{{ route('admin.tournaments.create') }}" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">New tournament</a>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                <tr>
                    <th class="px-4 py-3">ID</th>
                    <th class="px-4 py-3">Title</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Players</th>
                    <th class="px-4 py-3 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($tournaments as $t)
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs">{{ $t->id }}</td>
                        <td class="px-4 py-3 font-medium">{{ $t->title }}</td>
                        <td class="px-4 py-3"><span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium">{{ $t->status }}</span></td>
                        <td class="px-4 py-3">{{ $t->participants_count }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.tournaments.show', $t) }}" class="font-semibold text-blue-600 hover:text-blue-800">Manage</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">No tournaments yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $tournaments->links() }}</div>
</div>
@endsection
