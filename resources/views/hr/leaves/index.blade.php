@extends('layouts.app', [
    'title' => 'Leave Settings & Balances',
    'active' => 'Leave Settings',
    'pageTitle' => 'Leave Settings & Initial Balances'
])

@section('content')
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Leave Types &amp; Balances</h1>
            <p class="text-xs text-slate-500 mt-1">
                Configure leave policies, entitlements, and employee annual balance quotas
            </p>
        </div>

        @if ($tab === 'types')
            <button
                type="button"
                onclick="document.getElementById('addTypeModal').classList.remove('hidden')"
                class="inline-flex items-center gap-2 px-4 py-2 bg-[#0F766E] hover:bg-teal-800 text-white rounded-lg text-xs font-semibold shadow-2xs transition cursor-pointer"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Add Leave Type</span>
            </button>
        @else
            <button
                type="button"
                onclick="document.getElementById('setBalanceModal').classList.remove('hidden')"
                class="inline-flex items-center gap-2 px-4 py-2 bg-[#0F766E] hover:bg-teal-800 text-white rounded-lg text-xs font-semibold shadow-2xs transition cursor-pointer"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Set Balance Quota</span>
            </button>
        @endif
    </div>

    {{-- Tabs Navigation --}}
    <div class="border-b border-slate-200 mb-6">
        <nav class="flex gap-6 -mb-px text-xs font-semibold">
            <a
                href="{{ route('hr.leave-settings', ['tab' => 'types']) }}"
                class="py-3 border-b-2 transition {{ $tab === 'types' ? 'border-[#0F766E] text-[#0F766E]' : 'border-transparent text-slate-500 hover:text-slate-700' }}"
            >
                Leave Types Policy
            </a>
            <a
                href="{{ route('hr.leave-settings', ['tab' => 'balances']) }}"
                class="py-3 border-b-2 transition {{ $tab === 'balances' ? 'border-[#0F766E] text-[#0F766E]' : 'border-transparent text-slate-500 hover:text-slate-700' }}"
            >
                Employee Initial Balances
            </a>
        </nav>
    </div>

    {{-- Tab 1: Leave Types --}}
    @if ($tab === 'types')
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-2xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-400 font-semibold border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3.5">Name</th>
                            <th class="px-6 py-3.5">Paid / Unpaid</th>
                            <th class="px-6 py-3.5">Max Days/Year</th>
                            <th class="px-6 py-3.5">Attachment Required</th>
                            <th class="px-6 py-3.5 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($leaveTypes as $lt)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="px-6 py-3.5 font-semibold text-slate-900">
                                    {{ $lt->name }}
                                </td>
                                <td class="px-6 py-3.5">
                                    <span class="px-2 py-0.5 rounded text-[11px] font-medium {{ $lt->is_paid ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-700' }}">
                                        {{ $lt->is_paid ? 'Paid' : 'Unpaid' }}
                                    </span>
                                </td>
                                <td class="px-6 py-3.5 font-semibold text-slate-800">
                                    {{ $lt->max_days_per_year ? $lt->max_days_per_year . ' days' : 'Flexible' }}
                                </td>
                                <td class="px-6 py-3.5 text-slate-600">
                                    {{ $lt->requires_attachment ? 'Yes' : 'No' }}
                                </td>
                                <td class="px-6 py-3.5 text-right">
                                    <button
                                        type="button"
                                        onclick="openEditTypeModal({{ json_encode([
                                            'id' => $lt->id,
                                            'name' => $lt->name,
                                            'is_paid' => (bool) $lt->is_paid,
                                            'max_days_per_year' => $lt->max_days_per_year,
                                            'requires_attachment' => (bool) $lt->requires_attachment,
                                            'update_url' => route('hr.leave-types.update', $lt->id)
                                        ]) }})"
                                        class="px-2.5 py-1 bg-slate-100 hover:bg-teal-50 hover:text-teal-700 rounded text-xs font-semibold cursor-pointer"
                                    >
                                        Edit
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-xs text-slate-400">
                                    No leave types configured.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Tab 2: Initial Balances --}}
    @if ($tab === 'balances')
        <div class="bg-white border border-slate-200 rounded-xl p-4 mb-6 shadow-2xs">
            <form method="GET" action="{{ route('hr.leave-settings') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                <input type="hidden" name="tab" value="balances">

                <div>
                    <label for="year" class="block text-[11px] font-semibold text-slate-600 mb-1">Fiscal Year</label>
                    <select name="year" id="year" class="w-full px-3 py-1.5 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600">
                        @for ($y = date('Y') + 1; $y >= date('Y') - 2; $y--)
                            <option value="{{ $y }}" {{ (int) $currentYear === $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>

                <div>
                    <label for="employee_id" class="block text-[11px] font-semibold text-slate-600 mb-1">Filter Employee</label>
                    <select name="employee_id" id="employee_id" class="w-full px-3 py-1.5 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600">
                        <option value="">All Employees</option>
                        @foreach ($employees as $emp)
                            <option value="{{ $emp->id }}" {{ $selectedEmployeeId === $emp->id ? 'selected' : '' }}>
                                {{ $emp->full_name }} ({{ $emp->employee_code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="w-full py-1.5 px-3 bg-[#0F766E] hover:bg-teal-800 text-white rounded-lg text-xs font-semibold shadow-2xs transition cursor-pointer">
                        Apply
                    </button>
                    <a href="{{ route('hr.leave-settings', ['tab' => 'balances']) }}" class="py-1.5 px-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-xs font-medium text-center">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-2xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-400 font-semibold border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3.5">Employee</th>
                            <th class="px-6 py-3.5">Leave Type</th>
                            <th class="px-6 py-3.5">Year</th>
                            <th class="px-6 py-3.5">Total Opening Quota</th>
                            <th class="px-6 py-3.5">Used Days</th>
                            <th class="px-6 py-3.5">Remaining Days</th>
                            <th class="px-6 py-3.5 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($balances as $bal)
                            @php
                                $remaining = $bal->total_days - $bal->used_days;
                            @endphp
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="px-6 py-3.5">
                                    <div class="font-medium text-slate-900">{{ $bal->employee?->full_name }}</div>
                                    <div class="text-[10px] text-slate-400">{{ $bal->employee?->employee_code }} &middot; {{ $bal->employee?->department?->name }}</div>
                                </td>
                                <td class="px-6 py-3.5 font-medium text-slate-800">
                                    {{ $bal->leaveType?->name }}
                                </td>
                                <td class="px-6 py-3.5 text-slate-600 font-semibold">
                                    {{ $bal->year }}
                                </td>
                                <td class="px-6 py-3.5 font-semibold text-slate-800">
                                    {{ $bal->total_days }} days
                                </td>
                                <td class="px-6 py-3.5 text-slate-600">
                                    {{ $bal->used_days }} days
                                </td>
                                <td class="px-6 py-3.5">
                                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold {{ $remaining > 0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                                        {{ $remaining }} days
                                    </span>
                                </td>
                                <td class="px-6 py-3.5 text-right">
                                    <button
                                        type="button"
                                        onclick="openEditBalanceModal({{ json_encode([
                                            'employee_id' => $bal->employee_id,
                                            'leave_type_id' => $bal->leave_type_id,
                                            'year' => $bal->year,
                                            'total_days' => $bal->total_days,
                                            'used_days' => $bal->used_days,
                                        ]) }})"
                                        class="px-2.5 py-1 bg-slate-100 hover:bg-teal-50 hover:text-teal-700 rounded text-xs font-semibold cursor-pointer"
                                    >
                                        Edit
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-10 text-center text-xs text-slate-400">
                                    No leave balance allocations recorded for year {{ $currentYear }}.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($balances->hasPages())
                <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
                    {{ $balances->links() }}
                </div>
            @endif
        </div>
    @endif

    {{-- Add Leave Type Modal --}}
    <div id="addTypeModal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full overflow-hidden shadow-xl border border-slate-200">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                <h3 class="text-sm font-bold text-slate-900">Add Leave Type</h3>
                <button type="button" onclick="document.getElementById('addTypeModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-sm cursor-pointer">✕</button>
            </div>

            <form method="POST" action="{{ route('hr.leave-types.store') }}" class="p-6 space-y-4">
                @csrf
                <div>
                    <label for="lt_name" class="block text-xs font-semibold text-slate-700 mb-1">Leave Type Name *</label>
                    <input type="text" id="lt_name" name="name" required placeholder="e.g. Marriage Leave" class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600">
                </div>

                <div>
                    <label for="lt_max" class="block text-xs font-semibold text-slate-700 mb-1">Max Quota Days / Year</label>
                    <input type="number" id="lt_max" name="max_days_per_year" min="1" placeholder="Leave empty for unconstrained" class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600">
                </div>

                <div class="space-y-2 pt-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_paid" value="1" checked class="w-4 h-4 rounded text-teal-600 focus:ring-teal-500 border-slate-300">
                        <span class="text-xs text-slate-700">Paid Leave</span>
                    </label>

                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="requires_attachment" value="1" class="w-4 h-4 rounded text-teal-600 focus:ring-teal-500 border-slate-300">
                        <span class="text-xs text-slate-700">Requires Supporting Document (Doctor Note/Certificate)</span>
                    </label>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" onclick="document.getElementById('addTypeModal').classList.add('hidden')" class="px-4 py-2 border border-slate-300 text-slate-600 rounded-lg text-xs font-semibold hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-[#0F766E] hover:bg-teal-800 text-white rounded-lg text-xs font-semibold shadow-2xs transition">Save Type</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Leave Type Modal --}}
    <div id="editTypeModal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full overflow-hidden shadow-xl border border-slate-200">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                <h3 class="text-sm font-bold text-slate-900">Edit Leave Type</h3>
                <button type="button" onclick="document.getElementById('editTypeModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-sm cursor-pointer">✕</button>
            </div>

            <form id="editTypeForm" method="POST" action="" class="p-6 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label for="edit_lt_name" class="block text-xs font-semibold text-slate-700 mb-1">Leave Type Name *</label>
                    <input type="text" id="edit_lt_name" name="name" required class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600">
                </div>

                <div>
                    <label for="edit_lt_max" class="block text-xs font-semibold text-slate-700 mb-1">Max Quota Days / Year</label>
                    <input type="number" id="edit_lt_max" name="max_days_per_year" min="1" class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600">
                </div>

                <div class="space-y-2 pt-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="edit_lt_paid" name="is_paid" value="1" class="w-4 h-4 rounded text-teal-600 focus:ring-teal-500 border-slate-300">
                        <span class="text-xs text-slate-700">Paid Leave</span>
                    </label>

                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="edit_lt_attach" name="requires_attachment" value="1" class="w-4 h-4 rounded text-teal-600 focus:ring-teal-500 border-slate-300">
                        <span class="text-xs text-slate-700">Requires Supporting Document</span>
                    </label>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" onclick="document.getElementById('editTypeModal').classList.add('hidden')" class="px-4 py-2 border border-slate-300 text-slate-600 rounded-lg text-xs font-semibold hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-[#0F766E] hover:bg-teal-800 text-white rounded-lg text-xs font-semibold shadow-2xs transition">Update Type</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Set/Edit Balance Quota Modal --}}
    <div id="setBalanceModal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full overflow-hidden shadow-xl border border-slate-200">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                <h3 class="text-sm font-bold text-slate-900">Set Initial Balance Quota</h3>
                <button type="button" onclick="document.getElementById('setBalanceModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-sm cursor-pointer">✕</button>
            </div>

            <form method="POST" action="{{ route('hr.leave-balances.save') }}" class="p-6 space-y-4">
                @csrf
                <div>
                    <label for="bal_emp" class="block text-xs font-semibold text-slate-700 mb-1">Employee *</label>
                    <select id="bal_emp" name="employee_id" required class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600">
                        @foreach ($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->full_name }} ({{ $emp->employee_code }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="bal_type" class="block text-xs font-semibold text-slate-700 mb-1">Leave Type *</label>
                    <select id="bal_type" name="leave_type_id" required class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600">
                        @foreach ($leaveTypes as $lt)
                            <option value="{{ $lt->id }}">{{ $lt->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="bal_year" class="block text-xs font-semibold text-slate-700 mb-1">Year *</label>
                        <input type="number" id="bal_year" name="year" value="{{ $currentYear }}" required class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600">
                    </div>
                    <div>
                        <label for="bal_total" class="block text-xs font-semibold text-slate-700 mb-1">Total Quota (Days) *</label>
                        <input type="number" id="bal_total" name="total_days" step="0.5" min="0" required class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600">
                    </div>
                </div>

                <div>
                    <label for="bal_used" class="block text-xs font-semibold text-slate-700 mb-1">Used Days</label>
                    <input type="number" id="bal_used" name="used_days" step="0.5" min="0" value="0" class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600">
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" onclick="document.getElementById('setBalanceModal').classList.add('hidden')" class="px-4 py-2 border border-slate-300 text-slate-600 rounded-lg text-xs font-semibold hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-[#0F766E] hover:bg-teal-800 text-white rounded-lg text-xs font-semibold shadow-2xs transition">Save Quota</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        function openEditTypeModal(data) {
            document.getElementById('editTypeForm').action = data.update_url;
            document.getElementById('edit_lt_name').value = data.name;
            document.getElementById('edit_lt_max').value = data.max_days_per_year || '';
            document.getElementById('edit_lt_paid').checked = data.is_paid;
            document.getElementById('edit_lt_attach').checked = data.requires_attachment;
            document.getElementById('editTypeModal').classList.remove('hidden');
        }

        function openEditBalanceModal(data) {
            document.getElementById('bal_emp').value = data.employee_id;
            document.getElementById('bal_type').value = data.leave_type_id;
            document.getElementById('bal_year').value = data.year;
            document.getElementById('bal_total').value = data.total_days;
            document.getElementById('bal_used').value = data.used_days;
            document.getElementById('setBalanceModal').classList.remove('hidden');
        }
    </script>
    @endpush
@endsection

