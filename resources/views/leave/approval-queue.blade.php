@extends('layouts.app', [
    'title' => 'Leave Approvals',
    'active' => $isHr ? 'Leave & Requests' : 'Leave Approvals',
    'pageTitle' => $isHr ? 'Company Leave & Requests' : 'Leave Approvals'
])

@section('content')
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                {{ $isHr ? 'Company leave & requests' : 'Leave approvals queue' }}
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                {{ $isHr ? 'Review leave, permission, and sick applications across all departments' : 'Review and approve/reject leave, permission, and sick applications from direct reports' }}
            </p>
        </div>

        <span class="px-3 py-1 bg-amber-50 text-amber-700 border border-amber-200 rounded-lg text-xs font-semibold">
            {{ $isHr ? 'HR Company-wide Scope' : 'Manager Direct Reports Scope' }}
        </span>
    </div>

    {{-- Filter Bar --}}
    <div class="bg-white border border-slate-200 rounded-xl p-4 mb-6 shadow-2xs">
        @php
            $formRoute = $isHr ? route('hr.leave-requests') : route('manager.leave-approvals');
        @endphp
        <form method="GET" action="{{ $formRoute }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
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
                <label for="status" class="block text-[11px] font-semibold text-slate-600 mb-1">Request Status</label>
                <select name="status" id="status" class="w-full px-3 py-1.5 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600">
                    <option value="">All statuses</option>
                    <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending approval</option>
                    <option value="approved" {{ $status === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ $status === 'rejected' ? 'selected' : '' }}>Rejected</option>
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

    {{-- Requests Table --}}
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-2xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-400 font-semibold border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3.5">Employee</th>
                        <th class="px-6 py-3.5">Type</th>
                        <th class="px-6 py-3.5">Dates</th>
                        <th class="px-6 py-3.5">Duration</th>
                        <th class="px-6 py-3.5">Reason</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($requests as $req)
                        @php
                            $statusVal = $req->status?->value ?? (string) $req->status;
                            $isPending = strtolower($statusVal) === 'pending';

                            $statusBadge = match (strtolower($statusVal)) {
                                'approved', 'disetujui' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'rejected', 'ditolak' => 'bg-rose-50 text-rose-700 border-rose-200',
                                default => 'bg-amber-50 text-amber-700 border-amber-200',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-6 py-3.5">
                                <div class="font-medium text-slate-900">{{ $req->employee?->full_name }}</div>
                                <div class="text-[10px] text-slate-400">{{ $req->employee?->employee_code }} &middot; {{ $req->employee?->department?->name ?? 'General' }}</div>
                            </td>
                            <td class="px-6 py-3.5 font-medium text-slate-800">
                                {{ $req->leaveType?->name ?? 'Leave' }}
                            </td>
                            <td class="px-6 py-3.5 text-slate-600">
                                {{ \Carbon\Carbon::parse($req->start_date)->translatedFormat('M d, Y') }}
                                @if ($req->start_date !== $req->end_date)
                                    &ndash; {{ \Carbon\Carbon::parse($req->end_date)->translatedFormat('M d, Y') }}
                                @endif
                            </td>
                            <td class="px-6 py-3.5 font-semibold text-slate-800">
                                {{ $req->total_days }} {{ $req->total_days > 1 ? 'days' : 'day' }}
                            </td>
                            <td class="px-6 py-3.5 text-slate-600 max-w-xs truncate">
                                {{ $req->reason }}
                            </td>
                            <td class="px-6 py-3.5">
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-medium border {{ $statusBadge }}">
                                    {{ ucfirst($statusVal) }}
                                </span>
                            </td>
                            <td class="px-6 py-3.5 text-right">
                                <button
                                    type="button"
                                    onclick="openLeaveModal({{ json_encode([
                                        'id' => $req->id,
                                        'name' => $req->employee?->full_name,
                                        'code' => $req->employee?->employee_code,
                                        'dept' => $req->employee?->department?->name,
                                        'type' => $req->leaveType?->name,
                                        'dates' => \Carbon\Carbon::parse($req->start_date)->translatedFormat('M d, Y') . ($req->start_date !== $req->end_date ? ' - ' . \Carbon\Carbon::parse($req->end_date)->translatedFormat('M d, Y') : ''),
                                        'duration' => $req->total_days . ' days',
                                        'reason' => $req->reason,
                                        'attachment' => $req->attachment_url,
                                        'status' => $statusVal,
                                        'rejection_reason' => $req->rejection_reason,
                                        'is_pending' => $isPending,
                                        'action_url' => $isHr ? route('hr.leave-requests.submit', $req->id) : route('manager.leave-approvals.submit', $req->id)
                                    ]) }})"
                                    class="px-3 py-1 bg-[#0F766E] hover:bg-teal-800 text-white rounded text-xs font-semibold cursor-pointer transition shadow-2xs"
                                >
                                    {{ $isPending ? 'Decide' : 'Detail' }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-xs text-slate-400">
                                No leave requests match the selected filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($requests->hasPages())
            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
                {{ $requests->links() }}
            </div>
        @endif
    </div>

    {{-- Leave Decision Modal (W08) --}}
    <div id="leaveModal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full overflow-hidden shadow-xl border border-slate-200 transition-all">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                <div>
                    <h3 class="text-sm font-bold text-slate-900" id="leaveModalEmpName">Review Leave Request</h3>
                    <p class="text-[11px] text-slate-400" id="leaveModalMeta">-</p>
                </div>
                <button type="button" onclick="closeLeaveModal()" class="text-slate-400 hover:text-slate-600 text-sm cursor-pointer">✕</button>
            </div>

            <div class="p-6 space-y-4 text-xs">
                <div class="grid grid-cols-2 gap-3 bg-slate-50 p-3.5 rounded-xl border border-slate-200">
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Leave Type</span>
                        <span class="font-semibold text-slate-800" id="leaveModalType">-</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Duration</span>
                        <span class="font-semibold text-slate-800" id="leaveModalDuration">-</span>
                    </div>
                    <div class="col-span-2 pt-2 border-t border-slate-200/60">
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Dates Requested</span>
                        <span class="font-semibold text-slate-800" id="leaveModalDates">-</span>
                    </div>
                </div>

                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-bold mb-1">Employee Reason</span>
                    <div class="p-3 bg-white border border-slate-200 rounded-lg text-slate-700 leading-relaxed" id="leaveModalReason">
                        -
                    </div>
                </div>

                <div id="leaveModalAttachmentContainer" class="hidden">
                    <span class="text-slate-400 block text-[10px] uppercase font-bold mb-1">Supporting Document</span>
                    <a id="leaveModalAttachmentLink" href="#" target="_blank" class="inline-flex items-center gap-1.5 text-xs font-semibold text-teal-700 bg-teal-50 px-3 py-1.5 rounded-md border border-teal-200 hover:bg-teal-100">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        <span>View Attachment</span>
                    </a>
                </div>

                <form id="leaveDecisionForm" method="POST" action="" class="space-y-4 pt-2 border-t border-slate-100">
                    @csrf
                    <div>
                        <label for="rejection_reason" class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Decision Note <span class="text-rose-500 font-normal" id="leaveRejectReasonRequired">(Required when rejecting)</span>
                        </label>
                        <textarea
                            name="rejection_reason"
                            id="rejection_reason"
                            rows="2"
                            placeholder="Reason for rejecting the request (required if rejected)..."
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600"
                        ></textarea>
                    </div>

                    <div id="leaveModalActionButtons" class="flex items-center justify-end gap-2 pt-2">
                        <button
                            type="button"
                            onclick="closeLeaveModal()"
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

                    <div id="leaveModalReadonlyStatus" class="hidden text-center py-2 text-slate-500 font-medium">
                        This request has already been reviewed with status: <span id="leaveModalCurrentStatusBadge" class="font-bold"></span>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function openLeaveModal(data) {
            document.getElementById('leaveModalEmpName').textContent = data.name;
            document.getElementById('leaveModalMeta').textContent = `${data.code} · ${data.dept}`;
            document.getElementById('leaveModalType').textContent = data.type;
            document.getElementById('leaveModalDuration').textContent = data.duration;
            document.getElementById('leaveModalDates').textContent = data.dates;
            document.getElementById('leaveModalReason').textContent = data.reason || 'No additional remarks provided.';

            const attachContainer = document.getElementById('leaveModalAttachmentContainer');
            const attachLink = document.getElementById('leaveModalAttachmentLink');
            if (data.attachment) {
                attachLink.href = data.attachment;
                attachContainer.classList.remove('hidden');
            } else {
                attachContainer.classList.add('hidden');
            }

            const form = document.getElementById('leaveDecisionForm');
            form.action = data.action_url;

            const actionBtns = document.getElementById('leaveModalActionButtons');
            const readonlyStatus = document.getElementById('leaveModalReadonlyStatus');
            const reasonInput = document.getElementById('rejection_reason');

            if (data.is_pending) {
                actionBtns.classList.remove('hidden');
                readonlyStatus.classList.add('hidden');
                reasonInput.disabled = false;
                reasonInput.value = '';
            } else {
                actionBtns.classList.add('hidden');
                readonlyStatus.classList.remove('hidden');
                document.getElementById('leaveModalCurrentStatusBadge').textContent = data.status.toUpperCase();
                reasonInput.value = data.rejection_reason || '-';
                reasonInput.disabled = true;
            }

            document.getElementById('leaveModal').classList.remove('hidden');
        }

        function closeLeaveModal() {
            document.getElementById('leaveModal').classList.add('hidden');
        }
    </script>
    @endpush
@endsection

