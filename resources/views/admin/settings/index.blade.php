@extends('layouts.admin')

@section('content')
    <div class="space-y-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Admin Settings</h2>
            <p class="text-slate-500">Manage administrative accounts and system permissions.</p>
        </div>

        @if(session('success'))
            <div class="bg-emerald-50 border-l-4 border-emerald-400 p-4 rounded-lg">
                <p class="text-emerald-700 font-medium">{{ session('success') }}</p>
            </div>
        @endif

        <!-- Tabs Container -->
        <div x-data="{ activeTab: 'my-account' }" class="space-y-6">
            <!-- Tab Buttons -->
            <div class="flex space-x-1 bg-slate-100 p-1 rounded-xl w-fit">
                <button @click="activeTab = 'my-account'"
                    :class="activeTab === 'my-account' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                    class="px-6 py-2 rounded-lg text-sm font-bold transition">
                    My Account
                </button>
                {{-- @if(Auth::user()->role === 'master')
                <button @click="activeTab = 'accounts'"
                    :class="activeTab === 'accounts' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                    class="px-6 py-2 rounded-lg text-sm font-bold transition">
                    Admin Accounts
                </button>
                <button @click="activeTab = 'permissions'"
                    :class="activeTab === 'permissions' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                    class="px-6 py-2 rounded-lg text-sm font-bold transition">
                    Permissions Matrix
                </button>
                @endif --}}
            </div>

            <!-- Tab: My Account -->
            <div x-show="activeTab === 'my-account'" class="space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Update Password -->
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                        <h3 class="font-bold text-slate-900 mb-4">Security Settings</h3>
                        <p class="text-sm text-slate-500 mb-6">Update your login password to keep your account secure.</p>

                        <form action="{{ route('admin.settings.password') }}" method="POST" class="space-y-4">
                            @csrf
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500">Current Password</label>
                                <input type="password" name="current_password"
                                    class="w-full px-4 py-2 border rounded-lg text-sm" required>
                                @error('current_password') <p class="text-red-500 text-[10px] mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500">New Password</label>
                                <input type="password" name="password" class="w-full px-4 py-2 border rounded-lg text-sm"
                                    required>
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500">Confirm New Password</label>
                                <input type="password" name="password_confirmation"
                                    class="w-full px-4 py-2 border rounded-lg text-sm" required>
                            </div>
                            <button type="submit"
                                class="w-full bg-slate-900 text-white py-2 rounded-lg font-bold hover:bg-slate-800 transition">Update
                                Password</button>
                        </form>
                    </div>

                    <!-- Account Info -->
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                        <h3 class="font-bold text-slate-900 mb-4">Account Information</h3>
                        <div class="space-y-4">
                            <div class="p-4 bg-slate-50 rounded-xl">
                                <p class="text-xs font-bold text-slate-400 uppercase">Your Name</p>
                                <p class="text-slate-900 font-medium">{{ Auth::user()->name }}</p>
                            </div>
                            <div class="p-4 bg-slate-50 rounded-xl">
                                <p class="text-xs font-bold text-slate-400 uppercase">Email Address</p>
                                <p class="text-slate-900 font-medium">{{ Auth::user()->email }}</p>
                            </div>
                            <div class="p-4 bg-slate-50 rounded-xl">
                                <p class="text-xs font-bold text-slate-400 uppercase">Access Role</p>
                                <span
                                    class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-blue-100 text-blue-600">{{ Auth::user()->role }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab: Accounts -->
            <div x-show="activeTab === 'accounts'" class="space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Add New Admin -->
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                        <h3 class="font-bold text-slate-900 mb-4">Create New Admin</h3>
                        <form action="{{ route('admin.settings.admins.store') }}" method="POST" class="space-y-4">
                            @csrf
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500">Full Name</label>
                                <input type="text" name="name" class="w-full px-4 py-2 border rounded-lg text-sm" required>
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500">Email Address</label>
                                <input type="email" name="email" class="w-full px-4 py-2 border rounded-lg text-sm"
                                    required>
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500">Initial Password</label>
                                <input type="password" name="password" class="w-full px-4 py-2 border rounded-lg text-sm"
                                    required>
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500">Role Type</label>
                                <select name="role" class="w-full px-4 py-2 border rounded-lg text-sm bg-white" required>
                                    <option value="admin">Standard Admin</option>
                                    <option value="master">Master Admin</option>
                                </select>
                            </div>
                            <button type="submit"
                                class="w-full bg-blue-600 text-white py-2 rounded-lg font-bold hover:bg-blue-700 transition">Create
                                Account</button>
                        </form>
                    </div>

                    <!-- Admin List -->
                    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 font-bold text-slate-500">
                                <tr>
                                    <th class="px-6 py-4">Name</th>
                                    <th class="px-6 py-4">Email</th>
                                    <th class="px-6 py-4">Role</th>
                                    <th class="px-6 py-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                @foreach($admins as $admin)
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="px-6 py-4 font-medium text-slate-900">{{ $admin->name }}</td>
                                        <td class="px-6 py-4 text-slate-500">{{ $admin->email }}</td>
                                        <td class="px-6 py-4">
                                            <span
                                                class="px-2 py-1 rounded text-[10px] font-black uppercase {{ $admin->role === 'master' ? 'bg-purple-100 text-purple-600' : 'bg-blue-100 text-blue-600' }}">
                                                {{ $admin->role }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right space-x-2">
                                            @if($admin->id !== Auth::user()->id)
                                                <form action="{{ route('admin.settings.admins.delete', $admin->id) }}" method="POST"
                                                    class="inline">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-800 font-bold text-xs"
                                                        onclick="return confirm('Remove this admin?')">Delete</button>
                                                </form>
                                            @else
                                                <span class="text-slate-300 text-xs italic">Current User</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tab: Permissions Matrix (Placeholder/Visual only for now) -->
            <div x-show="activeTab === 'permissions'" class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                <h3 class="font-bold text-slate-900 mb-2">Granular Permissions Matrix</h3>
                <p class="text-sm text-slate-500 mb-6">Assign specific modules to standard administrators. Master admins
                    have bypass access to all modules.</p>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-slate-500 font-bold">
                            <tr>
                                <th class="px-4 py-3">Module</th>
                                <th class="px-4 py-3 text-center">View</th>
                                <th class="px-4 py-3 text-center">Edit/Action</th>
                                <th class="px-4 py-3 text-center">Delete</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @php $modules = ['User Management', 'Financial Adjustments', 'Game Statistics', 'Tournament Control', 'Forecast Management', 'Admin Management']; @endphp
                            @foreach($modules as $module)
                                <tr>
                                    <td class="px-4 py-4 font-medium text-slate-700">{{ $module }}</td>
                                    <td class="px-4 py-4 text-center">
                                        <input type="checkbox" checked disabled class="rounded border-slate-300 text-blue-600">
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        <input type="checkbox" class="rounded border-slate-300 text-blue-600">
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        <input type="checkbox" class="rounded border-slate-300 text-blue-600">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-6 flex justify-end">
                    <button
                        class="bg-slate-900 text-white px-6 py-2 rounded-lg font-bold opacity-50 cursor-not-allowed">Save
                        Matrix Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Alpine.js for Tabs if not already in layout -->
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
@endsection