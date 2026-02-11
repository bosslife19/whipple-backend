<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Whipple</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-slate-50 font-['Inter'] h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <div class="text-center mb-10">
            <h1 class="text-3xl font-bold text-slate-900 mb-2">Whipple Admin</h1>
            <p class="text-slate-500">Welcome back! Please enter your details.</p>
        </div>

        <div class="bg-white p-8 rounded-2xl shadow-xl shadow-slate-200/60 border border-slate-100">
            @if(session('status'))
                <div class="mb-4 p-3 bg-emerald-50 text-emerald-700 rounded-lg text-sm border border-emerald-100">
                    {{ session('status') }}
                </div>
            @endif

            <form action="{{ route('login') }}" method="POST" class="space-y-6">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-semibold text-slate-700 mb-2">Email Address</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" required 
                        class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" 
                        placeholder="admin@whipple.com">
                    @error('email')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <div class="flex justify-between mb-2">
                        <label for="password" class="text-sm font-semibold text-slate-700">Password</label>
                        <a href="{{ route('password.request') }}" class="text-sm font-semibold text-blue-600 hover:text-blue-700">Forgot?</a>
                    </div>
                    <input type="password" name="password" id="password" required 
                        class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" 
                        placeholder="••••••••">
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="remember" id="remember" class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500">
                    <label for="remember" class="ml-2 text-sm text-slate-600">Remember me for 30 days</label>
                </div>

                <button type="submit" class="w-full bg-slate-900 text-white py-3.5 rounded-xl font-semibold hover:bg-slate-800 transform active:scale-[0.98] transition-all shadow-lg shadow-slate-200">
                    Sign in to Account
                </button>
            </form>
        </div>
    </div>
</body>
</html>
