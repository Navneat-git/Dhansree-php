<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Sign In | Dhansree</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8 font-sans antialiased bg-slate-950 text-slate-100">
    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
        <!-- Brand Wordmark -->
        <a href="{{ url('/') }}" class="inline-flex items-center gap-2 mb-2">
            <span class="text-3xl font-extrabold tracking-tight text-white">
                <span class="text-amber-500">Dhansree</span>
            </span>
        </a>
        <h2 class="text-xl font-bold tracking-tight text-white mt-2">
            Administrator Sign In
        </h2>
        <p class="mt-1 text-xs text-slate-400">
            Secure store administration & catalog management
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4 sm:px-0">
        <div class="bg-slate-900/90 border border-slate-800 shadow-2xl rounded-2xl p-6 sm:p-8 backdrop-blur-xl">
            @if(session('status'))
                <div class="mb-5 rounded-xl bg-blue-950/60 border border-blue-800/80 p-3.5 text-xs text-blue-200">
                    {{ session('status') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-5 rounded-xl bg-rose-950/60 border border-rose-800/80 p-3.5 text-xs text-rose-300">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ url('/admin/login') }}" class="space-y-4.5">
                @csrf

                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                        Email Address
                    </label>
                    <input id="email" name="email" type="email" autocomplete="email" required
                           value="{{ old('email') }}"
                           placeholder="admin@dhansree.com"
                           class="block w-full rounded-xl bg-slate-950/80 border border-slate-700/80 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition-colors">
                </div>

                <div>
                    <label for="password" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                        Password
                    </label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required
                           placeholder="••••••••••••"
                           class="block w-full rounded-xl bg-slate-950/80 border border-slate-700/80 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition-colors">
                </div>

                <div class="pt-2">
                    <button type="submit"
                            class="w-full flex justify-center py-2.5 px-4 rounded-xl font-semibold text-sm text-slate-950 bg-amber-500 hover:bg-amber-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-500 shadow-md transition-colors duration-150">
                        Sign In
                    </button>
                </div>
            </form>

            <div class="mt-6 pt-5 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-500">
                <span class="flex items-center gap-1.5">
                    <svg class="size-3.5 text-amber-500/80" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                    Argon2id + TOTP 2FA
                </span>
                <a href="{{ url('/') }}" class="text-slate-400 hover:text-amber-400 transition-colors">Return to store</a>
            </div>
        </div>
    </div>
</body>
</html>
