@extends('layouts.app', [
    'title' => 'Add Employee',
    'active' => 'Employees',
    'pageTitle' => 'Add Employee'
])

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Add New Employee</h1>
            <p class="text-xs text-slate-500 mt-1">
                Register a new corporate employee record into Humify HRIS
            </p>
        </div>

        <a href="{{ route('hr.employees') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-xs font-semibold">
            &larr; Back to Directory
        </a>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-2xs max-w-4xl">
        <form method="POST" action="{{ route('hr.employees.store') }}" class="space-y-6">
            @csrf

            <div>
                <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-2 mb-4">Identity &amp; Personal Info</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="employee_code" class="block text-xs font-semibold text-slate-700 mb-1">Employee Code *</label>
                        <input
                            type="text"
                            id="employee_code"
                            name="employee_code"
                            value="{{ old('employee_code') }}"
                            required
                            placeholder="e.g. EMP-006"
                            class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600"
                        >
                    </div>

                    <div class="sm:col-span-2">
                        <label for="full_name" class="block text-xs font-semibold text-slate-700 mb-1">Official Full Name *</label>
                        <input
                            type="text"
                            id="full_name"
                            name="full_name"
                            value="{{ old('full_name') }}"
                            required
                            placeholder="e.g. Robert Johnson"
                            class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600"
                        >
                    </div>

                    <div>
                        <label for="email" class="block text-xs font-semibold text-slate-700 mb-1">Work Email *</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            placeholder="robert@humify.test"
                            class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600"
                        >
                    </div>

                    <div>
                        <label for="phone" class="block text-xs font-semibold text-slate-700 mb-1">Phone Number (Numbers Only)</label>
                        <input
                            type="number"
                            id="phone"
                            name="phone"
                            value="{{ old('phone') }}"
                            placeholder="081234567890"
                            class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600"
                        >
                    </div>

                    <div>
                        <label for="gender" class="block text-xs font-semibold text-slate-700 mb-1">Gender *</label>
                        <select name="gender" id="gender" required class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600">
                            <option value="L" {{ old('gender') === 'L' || old('gender') === 'male' ? 'selected' : '' }}>Male</option>
                            <option value="P" {{ old('gender') === 'P' || old('gender') === 'female' ? 'selected' : '' }}>Female</option>
                        </select>
                    </div>

                    <div>
                        <label for="birth_place" class="block text-xs font-semibold text-slate-700 mb-1">Birth Place</label>
                        <input
                            type="text"
                            id="birth_place"
                            name="birth_place"
                            value="{{ old('birth_place') }}"
                            placeholder="e.g. Jakarta"
                            class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600"
                        >
                    </div>

                    <div>
                        <label for="birth_date" class="block text-xs font-semibold text-slate-700 mb-1">Birth Date</label>
                        <input
                            type="date"
                            id="birth_date"
                            name="birth_date"
                            value="{{ old('birth_date') }}"
                            class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600"
                        >
                    </div>

                    <div class="sm:col-span-2">
                        <label for="address" class="block text-xs font-semibold text-slate-700 mb-1">Residential Address</label>
                        <input
                            type="text"
                            id="address"
                            name="address"
                            value="{{ old('address') }}"
                            placeholder="e.g. Jl. Sudirman No. 1"
                            class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600"
                        >
                    </div>
                </div>
            </div>

            <div>
                <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-2 mb-4">Organization &amp; Job Placement</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="department_id" class="block text-xs font-semibold text-slate-700 mb-1">Department *</label>
                        <select name="department_id" id="department_id" required class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600">
                            @foreach ($departments as $dept)
                                <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                    {{ $dept->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="position_id" class="block text-xs font-semibold text-slate-700 mb-1">Job Title / Position *</label>
                        <select name="position_id" id="position_id" required class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600">
                            @foreach ($positions as $pos)
                                <option value="{{ $pos->id }}" {{ old('position_id') == $pos->id ? 'selected' : '' }}>
                                    {{ $pos->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="supervisor_id" class="block text-xs font-semibold text-slate-700 mb-1">Direct Manager (Supervisor)</label>
                        <select name="supervisor_id" id="supervisor_id" class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600">
                            <option value="">None (Top-level)</option>
                            @foreach ($supervisors as $sup)
                                <option value="{{ $sup->id }}" {{ old('supervisor_id') == $sup->id ? 'selected' : '' }}>
                                    {{ $sup->full_name }} ({{ $sup->employee_code }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="employment_type" class="block text-xs font-semibold text-slate-700 mb-1">Employment Type *</label>
                        <select name="employment_type" id="employment_type" required class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600">
                            <option value="permanent" {{ old('employment_type') === 'permanent' ? 'selected' : '' }}>Permanent</option>
                            <option value="contract" {{ old('employment_type') === 'contract' ? 'selected' : '' }}>Contract</option>
                            <option value="internship" {{ old('employment_type') === 'internship' ? 'selected' : '' }}>Internship</option>
                            <option value="daily" {{ old('employment_type') === 'daily' ? 'selected' : '' }}>Daily</option>
                        </select>
                    </div>

                    <div>
                        <label for="employee_status" class="block text-xs font-semibold text-slate-700 mb-1">Status *</label>
                        <select name="employee_status" id="employee_status" required class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600">
                            <option value="active" {{ old('employee_status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('employee_status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>

                    <div>
                        <label for="hire_date" class="block text-xs font-semibold text-slate-700 mb-1">Hire Date *</label>
                        <input
                            type="date"
                            id="hire_date"
                            name="hire_date"
                            value="{{ old('hire_date', date('Y-m-d')) }}"
                            required
                            class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600"
                        >
                    </div>
                </div>

                <div class="mt-4 pt-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_shift_worker" value="1" {{ old('is_shift_worker') ? 'checked' : '' }} class="w-4 h-4 rounded text-teal-600 focus:ring-teal-500 border-slate-300">
                        <span class="text-xs font-medium text-slate-700">Shifting Work Category (Non-regular)</span>
                    </label>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="{{ route('hr.employees') }}" class="px-4 py-2 border border-slate-300 text-slate-600 rounded-lg text-xs font-semibold hover:bg-slate-50">
                    Cancel
                </a>
                <button type="submit" class="px-5 py-2 bg-[#0F766E] hover:bg-teal-800 text-white rounded-lg text-xs font-semibold shadow-2xs transition cursor-pointer">
                    Save Employee
                </button>
            </div>
        </form>
    </div>
@endsection

