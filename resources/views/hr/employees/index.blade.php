@extends('layouts.app', [
    'title' => 'Employees',
    'active' => 'Employees',
    'pageTitle' => 'Employee Management'
])

@section('content')
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Employees</h1>
            <p class="text-xs text-slate-500 mt-1">
                Manage organization directory, employee details, and face references
            </p>
        </div>

        <a href="{{ route('hr.employees.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-[#0F766E] hover:bg-teal-800 text-white rounded-lg text-xs font-semibold shadow-2xs transition cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Add Employee</span>
        </a>
    </div>

    {{-- Filter Bar --}}
    <div class="bg-white border border-slate-200 rounded-xl p-4 mb-6 shadow-2xs">
        <form method="GET" action="{{ route('hr.employees') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
            <div>
                <label for="search" class="block text-[11px] font-semibold text-slate-600 mb-1">Search Directory</label>
                <input
                    type="text"
                    id="search"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Name, ID code, or email..."
                    class="w-full px-3 py-1.5 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600"
                >
            </div>

            <div>
                <label for="department_id" class="block text-[11px] font-semibold text-slate-600 mb-1">Department</label>
                <select name="department_id" id="department_id" class="w-full px-3 py-1.5 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600">
                    <option value="">All Departments</option>
                    @foreach ($departments as $dept)
                        <option value="{{ $dept->id }}" {{ (string) $departmentId === (string) $dept->id ? 'selected' : '' }}>
                            {{ $dept->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="employee_status" class="block text-[11px] font-semibold text-slate-600 mb-1">Status</label>
                <select name="employee_status" id="employee_status" class="w-full px-3 py-1.5 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600">
                    <option value="">All Statuses</option>
                    <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="resigned" {{ $status === 'resigned' ? 'selected' : '' }}>Resigned</option>
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="w-full py-1.5 px-3 bg-[#0F766E] hover:bg-teal-800 text-white rounded-lg text-xs font-semibold shadow-2xs transition cursor-pointer">
                    Apply Filter
                </button>
                <a href="{{ route('hr.employees') }}" class="py-1.5 px-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-xs font-medium text-center">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- Employee Table --}}
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-2xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-400 font-semibold border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3.5">Employee Name &amp; Code</th>
                        <th class="px-6 py-3.5">Department</th>
                        <th class="px-6 py-3.5">Job Title</th>
                        <th class="px-6 py-3.5">Manager</th>
                        <th class="px-6 py-3.5">Category</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($employees as $emp)
                        @php
                            $statusVal = $emp->employee_status?->value ?? (string) $emp->employee_status;
                            $isActive = in_array(strtolower($statusVal), ['active', 'aktif']);
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-6 py-3.5">
                                <div class="font-medium text-slate-900">{{ $emp->full_name }}</div>
                                <div class="text-[10px] text-slate-400">{{ $emp->employee_code }} &middot; {{ $emp->email }}</div>
                            </td>
                            <td class="px-6 py-3.5 font-medium text-slate-800">
                                {{ $emp->department?->name ?? '-' }}
                            </td>
                            <td class="px-6 py-3.5 text-slate-600">
                                {{ $emp->position?->name ?? 'Staff' }}
                            </td>
                            <td class="px-6 py-3.5 text-slate-500">
                                {{ $emp->supervisor?->full_name ?? 'None' }}
                            </td>
                            <td class="px-6 py-3.5 text-slate-600">
                                <span class="px-2 py-0.5 rounded text-[11px] {{ $emp->is_shift_worker ? 'bg-indigo-50 text-indigo-700' : 'bg-slate-100 text-slate-700' }}">
                                    {{ $emp->is_shift_worker ? 'Shifting' : 'Regular' }}
                                </span>
                            </td>
                            <td class="px-6 py-3.5">
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-medium border {{ $isActive ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' }}">
                                    {{ ucfirst($statusVal) }}
                                </span>
                            </td>
                            <td class="px-6 py-3.5 text-right">
                                <a
                                    href="{{ route('hr.employees.show', $emp->id) }}"
                                    class="px-3 py-1 bg-slate-100 hover:bg-teal-50 hover:text-teal-700 text-slate-700 rounded text-xs font-semibold transition"
                                >
                                    Detail &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-xs text-slate-400">
                                No employee records match the search criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($employees->hasPages())
            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
                {{ $employees->links() }}
            </div>
        @endif
    </div>
@endsection

