<?php

namespace App\Http\Controllers;

use App\Models\AdminBalanceAdjustment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminUserBalanceController extends Controller
{
    public function adjust(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'kind' => 'required|string|in:wallet_add,wallet_remove,points_add,points_remove',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'nullable|string|max:500',
        ]);

        $user = User::query()->findOrFail($id);
        $admin = $request->user();

        DB::transaction(function () use ($request, $user, $admin): void {
            $kind = $request->input('kind');
            $amount = (float) $request->input('amount');

            match ($kind) {
                'wallet_add' => $user->increment('wallet_balance', $amount),
                'wallet_remove' => $user->decrement('wallet_balance', min($amount, max(0, (float) $user->wallet_balance))),
                'points_add' => $user->increment('whipple_point', $amount),
                'points_remove' => $user->decrement('whipple_point', min($amount, max(0, (float) $user->whipple_point))),
            };

            AdminBalanceAdjustment::query()->create([
                'user_id' => $user->id,
                'admin_user_id' => $admin->id,
                'kind' => $kind,
                'amount' => $amount,
                'reason' => $request->input('reason'),
            ]);

            $user->refresh();
        });

        return response()->json([
            'status' => true,
            'user' => [
                'id' => $user->id,
                'wallet_balance' => $user->wallet_balance,
                'whipple_point' => $user->whipple_point,
            ],
        ]);
    }
}
