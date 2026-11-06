<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login &middot; Humify</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 min-h-screen text-slate-900">
    <main class="flex min-h-screen flex-col lg:flex-row">
        <!-- Brand side panel (Teal) -->
        <section class="lg:w-5/12 bg-[#0F766E] text-white p-8 lg:p-14 flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-3 mb-12">
                    <div class="w-9 h-9 rounded-lg bg-teal-800/60 border border-teal-500/30 flex items-center justify-center font-bold text-lg text-teal-100">
                        H
                    </div>
                    <span class="text-2xl font-bold tracking-tight">Humify</span>
                </div>

                <div class="space-y-4 max-w-md">
                    <span class="inline-block text-[#99F6E4] text-xs font-semibold tracking-wider uppercase">People &middot; Clarity &middot; Every Day</span>
                    <h1 class="text-4xl lg:text-5xl font-semibold tracking-tight text-white leading-tight">A clearer view of your workday.</h1>
                    <p class="text-teal-100/90 text-sm lg:text-base leading-relaxed">
                        Your people, attendance and requests. Connected in one thoughtful workspace.
                    </p>
                </div>

                <div class="mt-10 space-y-4 bg-teal-800/40 border border-teal-600/30 rounded-xl p-5 max-w-md backdrop-blur-xs">
                    <div class="flex items-start gap-3.5">
                        <svg class="w-5 h-5 text-teal-300 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <p class="font-semibold text-sm text-white">Review with confidence</p>
                            <p class="text-xs text-teal-100/80 mt-0.5">Keep attendance and approvals moving smoothly.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3.5 border-t border-teal-700/40 pt-4">
                        <svg class="w-5 h-5 text-teal-300 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <div>
                            <p class="font-semibold text-sm text-white">People in one place</p>
                            <p class="text-xs text-teal-100/80 mt-0.5">Find the details and context that help your team work.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-8 text-xs text-teal-200/70 border-t border-teal-700/30">
                Humify &middot; Employee management, made human.
            </div>
        </section>

        <!-- Login Form Section -->
        <section class="lg:w-7/12 bg-white flex flex-col justify-center items-center p-6 sm:p-12">
            <div class="w-full max-w-md">
                <div class="mb-8">
                    <h2 class="text-2xl lg:text-3xl font-semibold text-slate-900 tracking-tight">Welcome back</h2>
                    <p class="text-sm text-slate-500 mt-1.5">Sign in with the corporate credentials provided by your HR team.</p>
                </div>

                @if (session('success'))
                    <div class="mb-5 p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-lg text-xs flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-5 p-3.5 bg-rose-50 border border-rose-200 text-rose-800 rounded-lg text-xs space-y-1">
                        @foreach ($errors->all() as $error)
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>{{ $error }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('login.post') }}" class="space-y-5">
                    @csrf
                    <div>
                        <label for="username" class="block text-xs font-semibold text-slate-700 mb-1.5">Username</label>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            value="{{ old('username') }}"
                            required
                            autofocus
                            placeholder="e.g. alex.morgan"
                            class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600 transition"
                        >
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="password" class="block text-xs font-semibold text-slate-700">Password</label>
                        </div>
                        <div class="relative">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                required
                                placeholder="Enter your password"
                                class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600 transition pr-10"
                            >
                            <button
                                type="button"
                                onclick="togglePasswordVisibility()"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs"
                                title="Show/Hide Password"
                            >
                                <svg id="eye-icon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="remember" class="w-4 h-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                            <span class="text-xs text-slate-600">Remember this device</span>
                        </label>
                    </div>

                    <button
                        type="submit"
                        class="w-full py-2.5 px-4 bg-[#0F766E] hover:bg-teal-800 text-white font-medium text-sm rounded-lg shadow-xs transition duration-150 flex items-center justify-center gap-2 cursor-pointer"
                    >
                        <span>Sign In</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </button>
                </form>

                <div class="mt-8 pt-6 border-t border-slate-200">
                    <p class="text-xs text-slate-500 text-center mb-3">
                        Need access or help signing in? Contact your corporate HR administrator.
                    </p>

                    <!-- Quick Demo Credentials helper (Loaded dynamically from database) -->
                    @php
                        $demoAccounts = \App\Models\User::with('employee')->where('is_active', true)->orderBy('username')->get();
                    @endphp
                    @if ($demoAccounts->isNotEmpty())
                        <div class="bg-slate-50 border border-slate-200 rounded-lg p-3 text-xs text-slate-600">
                            <p class="font-semibold text-slate-800 mb-1.5 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-teal-500 inline-block"></span>
                                Demo Accounts (Password: <code class="text-teal-700 bg-white px-1 py-0.5 rounded border border-slate-200">password123</code>):
                            </p>
                            <div class="grid grid-cols-2 gap-1.5 text-[11px] mt-2 max-h-40 overflow-y-auto">
                                @foreach ($demoAccounts as $account)
                                    <a href="{{ route('dev.switch', $account->username) }}" class="p-1.5 bg-white border border-slate-200 rounded hover:border-teal-500 hover:text-teal-700 block truncate" title="{{ $account->employee?->full_name }}">
                                        <strong>{{ $account->username }}</strong> ({{ $account->role_label }})
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    </main>

    <script>
        function togglePasswordVisibility() {
            const input = document.getElementById('password');
            if (input.type === 'password') {
                input.type = 'text';
            } else {
                input.type = 'password';
            }
        }
    </script>
</body>
</html>
