<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Whipple</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-slate-50 font-['Inter'] h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <div class="text-center mb-10">
            <h1 class="text-3xl font-bold text-slate-900 mb-2">Reset Password</h1>
            <p class="text-slate-500">Enter your email and we'll send you a link to reset.</p>
        </div>

        <div class="bg-white p-8 rounded-2xl shadow-xl shadow-slate-200/60 border border-slate-100">
            @if(session('status'))
                <div class="mb-4 p-3 bg-emerald-50 text-emerald-700 rounded-lg text-sm border border-emerald-100">
                    {{ session('status') }}
                </div>
            @endif

            <form action="{{ route('password.email') }}" method="POST" class="space-y-6">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-semibold text-slate-700 mb-2">Email Address</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" required 
                        class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" 
                        placeholder="you@example.com">
                    @error('email')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="w-full bg-slate-900 text-white py-3.5 rounded-xl font-semibold hover:bg-slate-800 transform active:scale-[0.98] transition-all shadow-lg shadow-slate-200">
                    Send Reset Link
                </button>

                <div class="text-center">
                    <a href="{{ route('login') }}" class="text-sm font-semibold text-blue-600 hover:text-blue-700">Back to Login</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
