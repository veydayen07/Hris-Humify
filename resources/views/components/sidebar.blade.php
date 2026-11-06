@php
    /** @var \App\Models\User $currentUser */
    $currentUser = auth()->user();
    $role = is_object($currentUser?->role) ? $currentUser->role->value : (string) $currentUser?->role;

    $items = [];

    if ($currentUser?->isSuperAdmin()) {
        $workspaceLabel = 'Super Admin workspace';
        $items = [
            ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'home'],
            ['label' => 'Employees', 'route' => 'hr.employees', 'icon' => 'users'],
            ['label' => 'Attendance Reviews', 'route' => 'hr.attendance-reviews', 'icon' => 'clipboard-check'],
            ['label' => 'Leave & Requests', 'route' => 'hr.leave-requests', 'icon' => 'calendar-check'],
            ['label' => 'Shift Settings', 'route' => 'hr.shifts', 'icon' => 'clock'],
            ['label' => 'Leave Settings', 'route' => 'hr.leave-settings', 'icon' => 'adjustments'],
            ['label' => 'Monthly Report', 'route' => 'hr.reports', 'icon' => 'chart-bar'],
            ['label' => 'Accounts & Roles', 'route' => 'admin.users', 'icon' => 'shield'],
            ['label' => 'My Profile', 'route' => 'profile', 'icon' => 'user'],
        ];
    } elseif ($currentUser?->isHr()) {
        $workspaceLabel = 'HR Administrator';
        $items = [
            ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'home'],
            ['label' => 'Employees', 'route' => 'hr.employees', 'icon' => 'users'],
            ['label' => 'Attendance Reviews', 'route' => 'hr.attendance-reviews', 'icon' => 'clipboard-check'],
            ['label' => 'Leave & Requests', 'route' => 'hr.leave-requests', 'icon' => 'calendar-check'],
            ['label' => 'Shift Settings', 'route' => 'hr.shifts', 'icon' => 'clock'],
            ['label' => 'Leave Settings', 'route' => 'hr.leave-settings', 'icon' => 'adjustments'],
            ['label' => 'Monthly Report', 'route' => 'hr.reports', 'icon' => 'chart-bar'],
            ['label' => 'My Profile', 'route' => 'profile', 'icon' => 'user'],
        ];
    } elseif ($currentUser?->isManager()) {
        $workspaceLabel = 'Manager workspace';
        $items = [
            ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'home'],
            ['label' => 'Attendance Reviews', 'route' => 'manager.attendance-reviews', 'icon' => 'clipboard-check'],
            ['label' => 'Leave Approvals', 'route' => 'manager.leave-approvals', 'icon' => 'calendar-check'],
            ['label' => 'Team History', 'route' => 'manager.team-history', 'icon' => 'user-group'],
            ['label' => 'My Profile', 'route' => 'profile', 'icon' => 'user'],
        ];
    } else {
        $workspaceLabel = 'Employee workspace';
        $items = [
            ['label' => 'My Dashboard', 'route' => 'dashboard', 'icon' => 'home'],
            ['label' => 'My Profile', 'route' => 'profile', 'icon' => 'user'],
            ['label' => 'Attendance History', 'route' => 'attendance.history', 'icon' => 'clock'],
            ['label' => 'Leave & Requests', 'route' => 'leave.requests', 'icon' => 'calendar'],
        ];
    }
@endphp

<aside
    class="py-6 flex flex-col justify-between fixed top-0 left-0 z-40 w-56 h-screen bg-white border-r border-slate-200 select-none overflow-y-auto"
    aria-label="Sidebar">
    <div class="flex flex-col">
        {{-- Logo & Brand --}}
        <div class="px-5 mb-5 flex items-center gap-2.5">
            <img src="img/logo.png" alt="" class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-white text-base shadow-xs">
            <div class="flex flex-col">
                <span class="text-base font-bold text-slate-900 tracking-tight leading-none">Humify</span>
                <span class="text-[10px] text-teal-700 font-semibold tracking-wide uppercase mt-0.5">HRIS Portal</span>
            </div>
        </div>

        {{-- Role / Workspace Badge --}}
        <div class="px-5 mb-3">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-medium bg-slate-100 text-slate-600 border border-slate-200 w-full">
                <span class="w-1.5 h-1.5 rounded-full bg-teal-600"></span>
                <span>{{ $workspaceLabel }}</span>
            </span>
        </div>

        {{-- Navigation Menu --}}
        <nav class="mt-2 px-3">
            <p class="px-2 mb-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400">Navigation</p>
            <ul class="flex flex-col gap-1">
                @foreach ($items as $item)
                    @php
                        $isActive = request()->routeIs($item['route']) || (isset($active) && $active === $item['label']);
                    @endphp
                    <li>
                        <a
                            href="{{ route($item['route']) }}"
                            class="px-3 py-2 rounded-md text-xs font-medium flex items-center justify-between transition-colors {{ $isActive ? 'text-[#0F766E] bg-teal-50/90 font-semibold' : 'text-slate-600 hover:text-[#0F766E] hover:bg-slate-50' }}"
                        >
                            <span class="truncate">{{ $item['label'] }}</span>
                            @if ($isActive)
                                <span class="w-1.5 h-1.5 rounded-full bg-[#0F766E]"></span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>
    </div>

    {{-- Bottom info & logout section --}}
    <div class="px-4 pt-4 border-t border-slate-200 flex flex-col gap-3">
        <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200">
            <p class="text-[11px] text-slate-500 leading-snug">
                Attendance check-in and new requests can only be performed via the <strong>Humify mobile app</strong>.
            </p>
        </div>

        <form method="POST" action="{{ route('logout') }}" class="w-full">
            @csrf
            <button
                type="submit"
                class="w-full text-xs text-slate-500 hover:text-rose-600 hover:bg-rose-50 p-2 rounded-md transition-colors flex items-center justify-between cursor-pointer"
            >
                <span>Sign out</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
            </button>
        </form>
    </div>
</aside>
