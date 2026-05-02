<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Transaction;
use App\Models\SkillGameMatchPlayers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%");
        }

        $users = $query->latest()->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function show($id)
    {
        $user = User::findOrFail($id);
        
        $stats = [
            'total_games' => SkillGameMatchPlayers::where('user_id', $user->id)->count(),
            'total_won' => SkillGameMatchPlayers::where('user_id', $user->id)->where('rank', 1)->count(),
            'total_lost' => SkillGameMatchPlayers::where('user_id', $user->id)->where('rank', '>', 1)->count(),
            'total_deposit' => Transaction::where('user_id', $user->id)->where('type', 'deposit')->where('status', 'completed')->sum('amount'),
            'total_withdrawal' => Transaction::where('user_id', $user->id)->where('type', 'withdrawal')->where('status', 'completed')->sum('amount'),
        ];

        $transactions = Transaction::where('user_id', $user->id)->latest()->paginate(10);

        return view('admin.users.show', compact('user', 'stats', 'transactions'));
    }

    public function updateBalance(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $request->validate([
            'amount' => 'required|numeric',
            'type' => 'required|in:add,remove',
            'description' => 'nullable|string'
        ]);

        $amount = $request->amount;
        if ($request->type === 'remove') {
            $amount = -$amount;
        }

        $user->increment('wallet_balance', $amount);

        Transaction::create([
            'user_id' => $user->id,
            'type' => $request->type === 'add' ? 'deposit' : 'withdrawal',
            'amount' => abs($amount),
            'status' => 'completed',
            'ref' => 'ADM_'.time().rand(100, 999),
            'description' => $request->description ?? "Admin adjustment: " . $request->type,
            'balance_after' => $user->wallet_balance
        ]);

        return back()->with('success', 'Balance updated successfully');
    }

    public function updatePoints(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $request->validate([
            'amount' => 'required|numeric',
            'type' => 'required|in:add,remove',
        ]);

        $amount = $request->amount;
        if ($request->type === 'remove') {
            $amount = -$amount;
        }

        $user->increment('whipple_point', $amount);

        return back()->with('success', 'Points updated successfully');
    }

    public function transactions(Request $request)
    {
        $query = Transaction::with('user');

        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->search) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            })->orWhere('ref', 'like', "%{$request->search}%");
        }

        $transactions = $query->latest()->paginate(50);
        $total_volume = Transaction::where('status', 'completed')->sum('amount');

        return view('admin.transactions.index', compact('transactions', 'total_volume'));
    }
}
