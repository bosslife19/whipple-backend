<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AdminSettingController extends Controller
{
    public function index()
    {
        $admins = [];
        if (Auth::user()->role === 'master') {
            $admins = User::whereIn('role', ['admin', 'master'])->get();
        }
        return view('admin.settings.index', compact('admins'));
    }

    public function storeAdmin(Request $request)
    {
        if (Auth::user()->role !== 'master') {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:4',
            'role' => 'required|in:admin,master',
            'permissions' => 'nullable|array'
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'permissions' => $request->permissions,
        ]);

        return back()->with('success', 'Admin created successfully');
    }

    public function updateAdmin(Request $request, $id)
    {
        if (Auth::user()->role !== 'master') {
            abort(403);
        }

        $admin = User::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$id,
            'role' => 'required|in:admin,master',
            'permissions' => 'nullable|array'
        ]);

        $admin->update([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'permissions' => $request->permissions,
        ]);

        if ($request->password) {
            $admin->update(['password' => Hash::make($request->password)]);
        }

        return back()->with('success', 'Admin updated successfully');
    }

    public function deleteAdmin($id)
    {
        if (Auth::user()->role !== 'master') {
            abort(403);
        }

        $admin = User::findOrFail($id);
        
        if ($admin->id === Auth::user()->id) {
            return back()->with('error', 'You cannot delete yourself');
        }

        $admin->delete();

        return back()->with('success', 'Admin deleted successfully');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:4|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'The provided password does not match our records.']);
        }

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return back()->with('success', 'Password updated successfully');
    }
}
