@php
    /** @var \App\Models\User $currentUser */
    $currentUser = auth()->user();
    $employee = $currentUser?->employee;
    $displayName = $employee?->full_name ?? $currentUser?->username ?? 'User';
    $roleLabel = $currentUser?->getRoleLabelAttribute() ?? 'User';

    $badgeStyle = match (true) {
        $currentUser?->isSuperAdmin() => 'bg-purple-50 text-purple-700 border-purple-200',
        $currentUser?->isHr() => 'bg-blue-50 text-blue-700 border-blue-200',
        $currentUser?->isManager() => 'bg-amber-50 text-amber-700 border-amber-200',
        default => 'bg-emerald-50 text-emerald-700 border-emerald-200',
    };
@endphp

<header class="h-14 flex items-center justify-between px-8 bg-white border-b border-slate-200 sticky top-0 z-30 shadow-2xs">
    <nav class="flex items-center gap-2 text-xs text-slate-400">
        <a href="{{ route('dashboard') }}" class="hover:text-slate-600 transition">Workspace</a>
        <span>/</span>
        <span class="text-slate-800 font-semibold">{{ $page ?? 'Dashboard' }}</span>
    </nav>

    <div class="flex items-center gap-4">
        {{-- Demo Switcher (Discreet for testing - loaded dynamically from database) --}}
        @if (app()->environment(['local', 'testing']))
            @php
                $demoAccounts = \App\Models\User::with(['employee'])->where('is_active', true)->orderBy('username')->get();
            @endphp
            <div class="relative group">
                <button type="button" class="px-2 py-1 text-[11px] font-medium bg-slate-100 hover:bg-slate-200 text-slate-600 rounded border border-slate-200 flex items-center gap-1 cursor-pointer">
                    <span>Switch Demo Role</span>
                    <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                </button>
                <div class="absolute right-0 mt-1 w-52 bg-white border border-slate-200 rounded-lg shadow-lg py-1.5 hidden group-hover:block z-50 text-xs max-h-72 overflow-y-auto">
                    <p class="px-3 py-1 text-[10px] uppercase font-bold text-slate-400 border-b border-slate-100">Select Account Role:</p>
                    @foreach ($demoAccounts as $account)
                        @php
                            $accountName = $account->employee?->full_name ?? $account->username;
                            $isSelected = $currentUser?->username === $account->username;
                        @endphp
                        <a href="{{ route('dev.switch', $account->username) }}" class="block px-3 py-1.5 hover:bg-teal-50 hover:text-teal-700 {{ $isSelected ? 'font-bold text-teal-700 bg-teal-50/50' : 'text-slate-700' }}">
                            {{ $accountName }} &middot; <span class="text-slate-400">{{ $account->role_label }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Role badge --}}
        <span class="px-2.5 py-0.5 text-[11px] font-semibold rounded-full border {{ $badgeStyle }}">
            {{ $roleLabel }}
        </span>

        {{-- User details --}}
        <div class="flex items-center gap-2.5 pl-2 border-l border-slate-200">
            <div class="w-8 h-8 rounded-full bg-teal-100 text-[#0F766E] font-semibold text-xs flex items-center justify-center border border-teal-200">
                {{ strtoupper(substr($displayName, 0, 1)) }}
            </div>
            <div class="flex flex-col">
                <span class="text-xs font-semibold text-slate-800 leading-tight">{{ $displayName }}</span>
                <span class="text-[10px] text-slate-400">{{ $employee?->employee_code ?? $currentUser?->username }}</span>
            </div>
        </div>
    </div>
</header>
