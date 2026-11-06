@extends('layouts.app', [
    'title' => $employee->full_name,
    'active' => 'Employees',
    'pageTitle' => 'Employee Details'
])

@section('content')
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('hr.employees') }}" class="text-xs text-slate-400 hover:text-slate-600 font-semibold">&larr; Directory</a>
                <span class="text-slate-300">/</span>
                <span class="text-xs text-slate-600 font-medium">{{ $employee->employee_code }}</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight mt-1">{{ $employee->full_name }}</h1>
            <p class="text-xs text-slate-500 mt-0.5">
                {{ $employee->position?->name ?? 'Staff' }} &middot; {{ $employee->department?->name ?? 'General' }}
            </p>
        </div>

        <div class="flex items-center gap-3">
            <span class="px-3 py-1 rounded-full text-xs font-semibold border {{ in_array($employee->employee_status?->value ?? (string) $employee->employee_status, ['active', 'aktif']) ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' }}">
                {{ ucfirst($employee->employee_status?->value ?? (string) $employee->employee_status) }}
            </span>
            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                {{ $employee->is_shift_worker ? 'Shifting Worker' : 'Regular Worker' }}
            </span>
        </div>
    </div>

    {{-- Tabs Navigation (W09: Personal, Job, Attendance, Face Reference) --}}
    <div class="border-b border-slate-200 mb-6">
        <nav class="flex gap-6 -mb-px text-xs font-semibold">
            <a
                href="{{ route('hr.employees.show', ['id' => $employee->id, 'tab' => 'personal']) }}"
                class="py-3 border-b-2 transition {{ $tab === 'personal' ? 'border-[#0F766E] text-[#0F766E]' : 'border-transparent text-slate-500 hover:text-slate-700' }}"
            >
                Personal Information
            </a>
            <a
                href="{{ route('hr.employees.show', ['id' => $employee->id, 'tab' => 'job']) }}"
                class="py-3 border-b-2 transition {{ $tab === 'job' ? 'border-[#0F766E] text-[#0F766E]' : 'border-transparent text-slate-500 hover:text-slate-700' }}"
            >
                Job Information
            </a>
            <a
                href="{{ route('hr.employees.show', ['id' => $employee->id, 'tab' => 'attendance']) }}"
                class="py-3 border-b-2 transition {{ $tab === 'attendance' ? 'border-[#0F766E] text-[#0F766E]' : 'border-transparent text-slate-500 hover:text-slate-700' }}"
            >
                Attendance History
            </a>
            <a
                href="{{ route('hr.employees.show', ['id' => $employee->id, 'tab' => 'face']) }}"
                class="py-3 border-b-2 transition {{ $tab === 'face' ? 'border-[#0F766E] text-[#0F766E]' : 'border-transparent text-slate-500 hover:text-slate-700' }}"
            >
                Reference Face Photo
            </a>
        </nav>
    </div>

    {{-- Tab 1: Personal Details --}}
    @if ($tab === 'personal')
        <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-2xs max-w-3xl">
            <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3 mb-5">Personal Information</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-4 gap-x-8 text-xs">
                <div>
                    <span class="text-slate-400 block text-[11px]">Official Full Name</span>
                    <span class="font-semibold text-slate-800 text-sm mt-0.5 block">{{ $employee->full_name }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[11px]">Gender</span>
                    <span class="font-medium text-slate-800 mt-0.5 block">{{ ucfirst($employee->gender?->value ?? (string) $employee->gender ?? '-') }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[11px]">Birth Place &amp; Date</span>
                    <span class="font-medium text-slate-800 mt-0.5 block">
                        {{ $employee->birth_place ?? '-' }}, {{ $employee->birth_date ? \Carbon\Carbon::parse($employee->birth_date)->format('d F Y') : '-' }}
                    </span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[11px]">Personal Email</span>
                    <span class="font-medium text-slate-800 mt-0.5 block">{{ $employee->email ?? '-' }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[11px]">Phone Number</span>
                    <span class="font-medium text-slate-800 mt-0.5 block">{{ $employee->phone ?? '-' }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[11px]">Residential Address</span>
                    <span class="font-medium text-slate-800 mt-0.5 block leading-relaxed">{{ $employee->address ?? '-' }}</span>
                </div>
            </div>

            <h4 class="text-xs font-bold text-slate-800 border-t border-slate-100 pt-5 mt-6 mb-3">Employment Summary</h4>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs bg-slate-50 p-3.5 rounded-lg border border-slate-200">
                <div>
                    <span class="text-slate-400 block text-[10px]">Employment Type</span>
                    <span class="font-semibold text-slate-800 capitalize">{{ $employee->employment_type?->value ?? (string) $employee->employment_type }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px]">Hire Date</span>
                    <span class="font-medium text-slate-800">{{ $employee->hire_date ? \Carbon\Carbon::parse($employee->hire_date)->format('d M Y') : '-' }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px]">Work Category</span>
                    <span class="font-medium text-slate-800">{{ $employee->is_shift_worker ? 'Shifting Worker' : 'Regular Worker' }}</span>
                </div>
            </div>
        </div>
    @endif

    {{-- Tab 2: Job & Org Details (with Edit Form) --}}
    @if ($tab === 'job')
        <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-2xs max-w-4xl">
            <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3 mb-5">Job &amp; Organizational Setup</h3>

            <form method="POST" action="{{ route('hr.employees.update', $employee->id) }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="full_name" class="block text-xs font-semibold text-slate-700 mb-1">Full Name</label>
                        <input
                            type="text"
                            id="full_name"
                            name="full_name"
                            value="{{ old('full_name', $employee->full_name) }}"
                            required
                            class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600"
                        >
                    </div>

                    <div>
                        <label for="department_id" class="block text-xs font-semibold text-slate-700 mb-1">Department</label>
                        <select name="department_id" id="department_id" required class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600">
                            @foreach ($departments as $dept)
                                <option value="{{ $dept->id }}" {{ old('department_id', $employee->department_id) == $dept->id ? 'selected' : '' }}>
                                    {{ $dept->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="position_id" class="block text-xs font-semibold text-slate-700 mb-1">Job Title</label>
                        <select name="position_id" id="position_id" required class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600">
                            @foreach ($positions as $pos)
                                <option value="{{ $pos->id }}" {{ old('position_id', $employee->position_id) == $pos->id ? 'selected' : '' }}>
                                    {{ $pos->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="supervisor_id" class="block text-xs font-semibold text-slate-700 mb-1">Direct Manager (Supervisor)</label>
                        <select name="supervisor_id" id="supervisor_id" class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600">
                            <option value="">None</option>
                            @foreach ($supervisors as $sup)
                                <option value="{{ $sup->id }}" {{ old('supervisor_id', $employee->supervisor_id) == $sup->id ? 'selected' : '' }}>
                                    {{ $sup->full_name }} ({{ $sup->employee_code }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="employment_type" class="block text-xs font-semibold text-slate-700 mb-1">Employment Type</label>
                        <select name="employment_type" id="employment_type" required class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600">
                            <option value="permanent" {{ in_array(old('employment_type', $employee->employment_type?->value ?? (string) $employee->employment_type), ['permanent', 'tetap']) ? 'selected' : '' }}>Permanent</option>
                            <option value="contract" {{ in_array(old('employment_type', $employee->employment_type?->value ?? (string) $employee->employment_type), ['contract', 'kontrak']) ? 'selected' : '' }}>Contract</option>
                            <option value="internship" {{ in_array(old('employment_type', $employee->employment_type?->value ?? (string) $employee->employment_type), ['internship', 'magang']) ? 'selected' : '' }}>Internship</option>
                            <option value="daily" {{ in_array(old('employment_type', $employee->employment_type?->value ?? (string) $employee->employment_type), ['daily', 'harian']) ? 'selected' : '' }}>Daily</option>
                        </select>
                    </div>

                    <div>
                        <label for="employee_status" class="block text-xs font-semibold text-slate-700 mb-1">Status</label>
                        <select name="employee_status" id="employee_status" required class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600">
                            <option value="active" {{ in_array(old('employee_status', $employee->employee_status?->value ?? (string) $employee->employee_status), ['active', 'aktif']) ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ in_array(old('employee_status', $employee->employee_status?->value ?? (string) $employee->employee_status), ['inactive', 'nonaktif']) ? 'selected' : '' }}>Inactive</option>
                            <option value="resigned" {{ in_array(old('employee_status', $employee->employee_status?->value ?? (string) $employee->employee_status), ['resigned', 'resign']) ? 'selected' : '' }}>Resigned</option>
                        </select>
                    </div>

                    <div>
                        <label for="hire_date" class="block text-xs font-semibold text-slate-700 mb-1">Hire Date</label>
                        <input
                            type="date"
                            id="hire_date"
                            name="hire_date"
                            value="{{ old('hire_date', $employee->hire_date ? \Carbon\Carbon::parse($employee->hire_date)->format('Y-m-d') : '') }}"
                            required
                            class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600"
                        >
                    </div>

                    <div>
                        <label for="resign_date" class="block text-xs font-semibold text-slate-700 mb-1">Resign Date (if any)</label>
                        <input
                            type="date"
                            id="resign_date"
                            name="resign_date"
                            value="{{ old('resign_date', $employee->resign_date ? \Carbon\Carbon::parse($employee->resign_date)->format('Y-m-d') : '') }}"
                            class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600"
                        >
                    </div>
                </div>

                <div class="mt-4 pt-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_shift_worker" value="1" {{ old('is_shift_worker', $employee->is_shift_worker) ? 'checked' : '' }} class="w-4 h-4 rounded text-teal-600 focus:ring-teal-500 border-slate-300">
                        <span class="text-xs font-medium text-slate-700">Shifting Work Category (Non-regular 08:00 - 17:00)</span>
                    </label>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end">
                    <button type="submit" class="px-5 py-2 bg-[#0F766E] hover:bg-teal-800 text-white rounded-lg text-xs font-semibold shadow-2xs transition cursor-pointer">
                        Update Organization Details
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- Tab 3: Attendance History --}}
    @if ($tab === 'attendance')
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-2xs">
            <div class="px-6 py-4 border-b border-slate-200">
                <h3 class="text-sm font-semibold text-slate-900">Attendance Log for {{ $employee->full_name }}</h3>
                <p class="text-xs text-slate-400 mt-0.5">Recorded mobile attendance records</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-400 font-semibold border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3">Date</th>
                            <th class="px-6 py-3">Shift</th>
                            <th class="px-6 py-3">Check-in / Out</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">Review</th>
                            <th class="px-6 py-3">Note</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($employee->attendances as $att)
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-3.5 font-medium text-slate-800">
                                    {{ \Carbon\Carbon::parse($att->attendance_date)->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-3.5 text-slate-500">
                                    {{ $att->workShift?->name ?? 'Standard' }}
                                </td>
                                <td class="px-6 py-3.5 font-mono text-slate-700">
                                    {{ $att->check_in_time ? \Carbon\Carbon::parse($att->check_in_time)->format('H:i') : '--' }} - {{ $att->check_out_time ? \Carbon\Carbon::parse($att->check_out_time)->format('H:i') : '--' }}
                                </td>
                                <td class="px-6 py-3.5">
                                    <span class="px-2 py-0.5 rounded text-[11px] bg-slate-100 text-slate-700">
                                        {{ ucfirst($att->status?->value ?? (string) $att->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-3.5">
                                    <span class="px-2 py-0.5 rounded text-[11px] {{ in_array($att->review_status?->value ?? (string) $att->review_status, ['approved', 'disetujui']) ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                        {{ ucfirst($att->review_status?->value ?? (string) $att->review_status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-3.5 text-slate-400">
                                    {{ $att->review_note ?? '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-xs text-slate-400">
                                    No attendance records found for this employee.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Tab 4: Reference Face Photo (W10 - HR Reference Face Update) --}}
    @if ($tab === 'face')
        <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-2xs max-w-2xl">
            <div class="mb-5 pb-4 border-b border-slate-100">
                <h3 class="text-sm font-bold text-slate-900">Reference Face Photo</h3>
                <p class="text-xs text-slate-500 mt-0.5">
                    The biometric reference photo is used by the mobile AI app for face matching during attendance check-in. Updates can only be performed by HR Admins.
                </p>
            </div>

            <div class="flex flex-col sm:flex-row gap-6 items-start mb-6">
                {{-- Current Reference Photo Preview --}}
                <div class="flex flex-col items-center">
                    <span class="text-[11px] font-semibold text-slate-500 mb-2">Current Reference Photo</span>
                    <div class="w-36 h-36 rounded-xl bg-slate-100 border-2 border-slate-200 overflow-hidden flex items-center justify-center shadow-inner">
                        @if ($employee->face_reference_photo_url)
                            <img src="{{ $employee->face_reference_photo_url }}" alt="Reference Face" class="w-full h-full object-cover">
                        @else
                            <div class="text-center p-3 text-slate-400">
                                <svg class="w-8 h-8 mx-auto mb-1 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                <span class="text-[10px]">No reference photo registered</span>
                            </div>
                        @endif
                    </div>
                    @if ($employee->face_registered_at)
                        <span class="text-[10px] text-slate-400 mt-2 text-center">
                            Registered: {{ \Carbon\Carbon::parse($employee->face_registered_at)->format('d M Y, H:i') }}
                        </span>
                    @endif
                </div>

                {{-- Upload New Reference Photo Form (W10) --}}
                <div class="flex-1 space-y-4">
                    <form method="POST" action="{{ route('hr.employees.face-reference', $employee->id) }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf

                        <div>
                            <label for="face_photo" class="block text-xs font-semibold text-slate-700 mb-1">Upload New Reference Face Photo</label>
                            <input
                                type="file"
                                id="face_photo"
                                name="face_photo"
                                accept="image/*"
                                class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100 cursor-pointer border border-slate-300 rounded-lg p-1.5"
                            >
                            <p class="text-[11px] text-slate-400 mt-1">Ensure the face clearly faces the camera without dark glasses or masks.</p>
                        </div>

                        <div>
                            <label for="photo_url" class="block text-xs font-semibold text-slate-700 mb-1">Or Enter Image URL (Optional)</label>
                            <input
                                type="url"
                                id="photo_url"
                                name="photo_url"
                                placeholder="https://example.com/face.jpg"
                                class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600"
                            >
                        </div>

                        <div class="pt-2">
                            <button
                                type="submit"
                                onclick="return confirm('Are you sure you want to update this employee reference face photo? Changes will take effect immediately for mobile attendance verification.')"
                                class="w-full py-2.5 px-4 bg-[#0F766E] hover:bg-teal-800 text-white rounded-lg text-xs font-semibold shadow-2xs transition cursor-pointer"
                            >
                                Confirm &amp; Update Reference Face
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection

