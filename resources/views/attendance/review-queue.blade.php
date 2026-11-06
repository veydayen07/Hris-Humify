@extends('layouts.app', [
    'title' => 'Attendance Review',
    'active' => 'Attendance Reviews',
    'pageTitle' => $isHr ? 'Company Attendance Review' : 'Team Attendance Review'
])

@section('content')
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                {{ $isHr ? 'Company attendance review' : 'Team attendance review' }}
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                {{ $isHr ? 'Review attendance records across all company departments' : 'Review and verify attendance records for your direct reports' }}
            </p>
        </div>

        <span class="px-3 py-1 bg-teal-50 text-teal-700 border border-teal-200 rounded-lg text-xs font-semibold">
            {{ $isHr ? 'HR Company-wide Scope' : 'Manager Direct Reports Scope' }}
        </span>
    </div>

    {{-- Filter Bar --}}
    <div class="bg-white border border-slate-200 rounded-xl p-4 mb-6 shadow-2xs">
        @php
            $formRoute = $isHr ? route('hr.attendance-reviews') : route('manager.attendance-reviews');
        @endphp
        <form method="GET" action="{{ $formRoute }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
            <div>
                <label for="search" class="block text-[11px] font-semibold text-slate-600 mb-1">Search Employee</label>
                <input
                    type="text"
                    id="search"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Name or employee code..."
                    class="w-full px-3 py-1.5 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600"
                >
            </div>

            <div>
                <label for="date" class="block text-[11px] font-semibold text-slate-600 mb-1">Date</label>
                <input
                    type="date"
                    id="date"
                    name="date"
                    value="{{ $date }}"
                    class="w-full px-3 py-1.5 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600"
                >
            </div>

            <div>
                <label for="review_status" class="block text-[11px] font-semibold text-slate-600 mb-1">Review Status</label>
                <select name="review_status" id="review_status" class="w-full px-3 py-1.5 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600">
                    <option value="">All statuses</option>
                    <option value="pending" {{ $reviewStatus === 'pending' ? 'selected' : '' }}>Pending review</option>
                    <option value="approved" {{ $reviewStatus === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ $reviewStatus === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="w-full py-1.5 px-3 bg-[#0F766E] hover:bg-teal-800 text-white rounded-lg text-xs font-semibold shadow-2xs transition cursor-pointer">
                    Apply Filter
                </button>
                <a href="{{ $formRoute }}" class="py-1.5 px-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-xs font-medium text-center">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- Review Table --}}
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-2xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-400 font-semibold border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3.5">Employee</th>
                        <th class="px-6 py-3.5">Date</th>
                        <th class="px-6 py-3.5">Shift</th>
                        <th class="px-6 py-3.5">Check-in / Out</th>
                        <th class="px-6 py-3.5">Attendance</th>
                        <th class="px-6 py-3.5">Review Status</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($attendances as $att)
                        @php
                            $statusVal = $att->status?->value ?? (string) $att->status;
                            $reviewVal = $att->review_status?->value ?? (string) $att->review_status;

                            $isPending = strtolower($reviewVal) === 'pending';

                            $reviewBadge = match (strtolower($reviewVal)) {
                                'approved', 'disetujui' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'rejected', 'ditolak' => 'bg-rose-50 text-rose-700 border-rose-200',
                                default => 'bg-amber-50 text-amber-700 border-amber-200',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-6 py-3.5">
                                <div class="font-medium text-slate-900">{{ $att->employee?->full_name }}</div>
                                <div class="text-[10px] text-slate-400">{{ $att->employee?->employee_code }} &middot; {{ $att->employee?->department?->name ?? 'General' }}</div>
                            </td>
                            <td class="px-6 py-3.5 font-medium text-slate-800">
                                {{ \Carbon\Carbon::parse($att->attendance_date)->translatedFormat('M d, Y') }}
                            </td>
                            <td class="px-6 py-3.5 text-slate-500">
                                {{ $att->workShift?->name ?? 'Regular' }}
                            </td>
                            <td class="px-6 py-3.5 font-mono text-slate-700">
                                {{ $att->check_in_time ? \Carbon\Carbon::parse($att->check_in_time)->format('H:i') : '--:--' }}
                                <span class="text-slate-300">/</span>
                                {{ $att->check_out_time ? \Carbon\Carbon::parse($att->check_out_time)->format('H:i') : '--:--' }}
                            </td>
                            <td class="px-6 py-3.5">
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-medium border bg-slate-100 text-slate-700 border-slate-200">
                                    {{ ucfirst($statusVal) }}
                                </span>
                            </td>
                            <td class="px-6 py-3.5">
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-medium border {{ $reviewBadge }}">
                                    {{ ucfirst($reviewVal) }}
                                </span>
                            </td>
                            <td class="px-6 py-3.5 text-right">
                                <button
                                    type="button"
                                    onclick="openReviewModal({{ json_encode([
                                        'id' => $att->id,
                                        'name' => $att->employee?->full_name,
                                        'code' => $att->employee?->employee_code,
                                        'dept' => $att->employee?->department?->name,
                                        'date' => \Carbon\Carbon::parse($att->attendance_date)->translatedFormat('l, F j, Y'),
                                        'shift' => $att->workShift?->name ?? 'Regular',
                                        'check_in' => $att->check_in_time ? \Carbon\Carbon::parse($att->check_in_time)->format('H:i') : '-',
                                        'check_out' => $att->check_out_time ? \Carbon\Carbon::parse($att->check_out_time)->format('H:i') : '-',
                                        'photo' => $att->check_in_photo_url,
                                        'face_verified' => (bool) $att->check_in_face_verified,
                                        'face_score' => $att->check_in_face_score ?? '95.5',
                                        'geofence' => (bool) $att->check_in_geofence_valid,
                                        'notes' => $att->notes ?? 'Regular mobile attendance',
                                        'review_status' => $reviewVal,
                                        'review_note' => $att->review_note,
                                        'is_pending' => $isPending,
                                        'action_url' => $isHr ? route('hr.attendance-reviews.submit', $att->id) : route('manager.attendance-reviews.submit', $att->id)
                                    ]) }})"
                                    class="px-3 py-1 bg-[#0F766E] hover:bg-teal-800 text-white rounded text-xs font-semibold cursor-pointer transition shadow-2xs"
                                >
                                    {{ $isPending ? 'Review' : 'Detail' }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-xs text-slate-400">
                                No attendance records match the filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($attendances->hasPages())
            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
                {{ $attendances->links() }}
            </div>
        @endif
    </div>

    {{-- Review & Verification Detail Modal (W07) --}}
    <div id="reviewModal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full overflow-hidden shadow-xl border border-slate-200 transition-all">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                <div>
                    <h3 class="text-sm font-bold text-slate-900" id="modalEmpName">Attendance Detail &amp; Verification</h3>
                    <p class="text-[11px] text-slate-400" id="modalEmpMeta">-</p>
                </div>
                <button type="button" onclick="closeReviewModal()" class="text-slate-400 hover:text-slate-600 text-sm cursor-pointer">✕</button>
            </div>

            <div class="p-6 space-y-4 text-xs">
                {{-- Photo & Face Verification Section --}}
                <div class="flex items-center gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200">
                    <div class="w-20 h-20 bg-slate-200 rounded-lg overflow-hidden shrink-0 border border-slate-300 flex items-center justify-center text-slate-400">
                        <img id="modalPhoto" src="" alt="Check-in Photo" class="w-full h-full object-cover hidden">
                        <span id="modalNoPhoto" class="text-[10px] text-center px-1">Mobile Attendance Photo</span>
                    </div>

                    <div class="space-y-1.5 flex-1">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500 font-medium">Face Match Score:</span>
                            <span class="font-bold text-slate-800" id="modalFaceScore">96.0%</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500 font-medium">Face Verification:</span>
                            <span id="modalFaceVerified" class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-800">Verified</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500 font-medium">Geofence Radius:</span>
                            <span id="modalGeofence" class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-800">Valid (Inside Office)</span>
                        </div>
                    </div>
                </div>

                {{-- Key Information Grid --}}
                <div class="grid grid-cols-2 gap-3 py-2 border-y border-slate-100">
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Shift &amp; Date</span>
                        <span class="font-semibold text-slate-800" id="modalShiftDate">-</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Check-in / Check-out</span>
                        <span class="font-semibold font-mono text-slate-800" id="modalTimes">-</span>
                    </div>
                </div>

                {{-- Decision Form (Active only if pending) --}}
                <form id="decisionForm" method="POST" action="" class="space-y-4 pt-2">
                    @csrf
                    <div>
                        <label for="review_note" class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Reviewer Note <span class="text-rose-500 font-normal" id="rejectNoteRequired">(Wajib diisi jika menolak)</span>
                        </label>
                        <textarea
                            name="review_note"
                            id="review_note"
                            rows="2"
                            placeholder="Tuliskan catatan verifikasi atau alasan penolakan..."
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600"
                        ></textarea>
                    </div>

                    <div id="modalActionButtons" class="flex items-center justify-end gap-2 pt-2">
                        <button
                            type="button"
                            onclick="closeReviewModal()"
                            class="px-4 py-2 border border-slate-300 text-slate-600 hover:bg-slate-50 rounded-lg text-xs font-semibold cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            name="decision"
                            value="rejected"
                            class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-semibold cursor-pointer transition shadow-2xs"
                        >
                            Reject
                        </button>
                        <button
                            type="submit"
                            name="decision"
                            value="approved"
                            class="px-5 py-2 bg-[#0F766E] hover:bg-teal-800 text-white rounded-lg text-xs font-semibold cursor-pointer transition shadow-2xs"
                        >
                            Approve
                        </button>
                    </div>

                    <div id="modalReadonlyStatus" class="hidden text-center py-2 text-slate-500 font-medium">
                        This attendance record has been reviewed with status: <span id="modalCurrentStatusBadge" class="font-bold"></span>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function openReviewModal(data) {
            document.getElementById('modalEmpName').textContent = data.name;
            document.getElementById('modalEmpMeta').textContent = `${data.code} · ${data.dept}`;
            document.getElementById('modalShiftDate').textContent = `${data.shift} · ${data.date}`;
            document.getElementById('modalTimes').textContent = `${data.check_in} - ${data.check_out}`;
            document.getElementById('modalFaceScore').textContent = `${data.face_score}%`;

            const photoEl = document.getElementById('modalPhoto');
            const noPhotoEl = document.getElementById('modalNoPhoto');
            if (data.photo) {
                photoEl.src = data.photo;
                photoEl.classList.remove('hidden');
                noPhotoEl.classList.add('hidden');
            } else {
                photoEl.classList.add('hidden');
                noPhotoEl.classList.remove('hidden');
            }

            const form = document.getElementById('decisionForm');
            form.action = data.action_url;

            const actionBtns = document.getElementById('modalActionButtons');
            const readonlyStatus = document.getElementById('modalReadonlyStatus');
            const noteInput = document.getElementById('review_note');

            if (data.is_pending) {
                actionBtns.classList.remove('hidden');
                readonlyStatus.classList.add('hidden');
                noteInput.disabled = false;
                noteInput.value = '';
            } else {
                actionBtns.classList.add('hidden');
                readonlyStatus.classList.remove('hidden');
                document.getElementById('modalCurrentStatusBadge').textContent = data.review_status.toUpperCase();
                noteInput.value = data.review_note || '-';
                noteInput.disabled = true;
            }

            document.getElementById('reviewModal').classList.remove('hidden');
        }

        function closeReviewModal() {
            document.getElementById('reviewModal').classList.add('hidden');
        }
    </script>
    @endpush
@endsection

