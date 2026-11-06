@extends('layouts.app', [
    'title' => 'Shift Settings',
    'active' => 'Shift Settings',
    'pageTitle' => 'Work Shift Configuration'
])

@section('content')
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Shift Settings</h1>
            <p class="text-xs text-slate-500 mt-1">
                Configure corporate work shifts, schedules, and regular/shifting categories
            </p>
        </div>

        <button
            type="button"
            onclick="document.getElementById('addShiftModal').classList.remove('hidden')"
            class="inline-flex items-center gap-2 px-4 py-2 bg-[#0F766E] hover:bg-teal-800 text-white rounded-lg text-xs font-semibold shadow-2xs transition cursor-pointer"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Add Shift</span>
        </button>
    </div>

    {{-- Shifts Table --}}
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-2xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-400 font-semibold border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3.5">Shift Name</th>
                        <th class="px-6 py-3.5">Start Time</th>
                        <th class="px-6 py-3.5">End Time</th>
                        <th class="px-6 py-3.5">Category</th>
                        <th class="px-6 py-3.5">Overnight</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($shifts as $shift)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-6 py-3.5 font-semibold text-slate-900">
                                {{ $shift->name }}
                            </td>
                            <td class="px-6 py-3.5 font-mono text-slate-800">
                                {{ substr($shift->start_time, 0, 5) }}
                            </td>
                            <td class="px-6 py-3.5 font-mono text-slate-800">
                                {{ substr($shift->end_time, 0, 5) }}
                            </td>
                            <td class="px-6 py-3.5">
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-medium border {{ $shift->category?->value === 'regular' || $shift->category === 'regular' ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-purple-50 text-purple-700 border-purple-200' }}">
                                    {{ ucfirst($shift->category?->value ?? (string) $shift->category) }}
                                </span>
                            </td>
                            <td class="px-6 py-3.5 text-slate-600">
                                {{ $shift->is_overnight ? 'Yes (Lintas Hari)' : 'No' }}
                            </td>
                            <td class="px-6 py-3.5">
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-medium border {{ $shift->is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200' }}">
                                    {{ $shift->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-3.5 text-right">
                                <button
                                    type="button"
                                    onclick="openEditShiftModal({{ json_encode([
                                        'id' => $shift->id,
                                        'name' => $shift->name,
                                        'start_time' => substr($shift->start_time, 0, 5),
                                        'end_time' => substr($shift->end_time, 0, 5),
                                        'category' => $shift->category?->value ?? (string) $shift->category,
                                        'is_overnight' => (bool) $shift->is_overnight,
                                        'is_active' => (bool) $shift->is_active,
                                        'update_url' => route('hr.shifts.update', $shift->id),
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
                                No work shift configurations found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Add Shift Modal (W11) --}}
    <div id="addShiftModal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full overflow-hidden shadow-xl border border-slate-200">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                <h3 class="text-sm font-bold text-slate-900">Add New Shift</h3>
                <button type="button" onclick="document.getElementById('addShiftModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-sm cursor-pointer">✕</button>
            </div>

            <form method="POST" action="{{ route('hr.shifts.store') }}" class="p-6 space-y-4">
                @csrf
                <div>
                    <label for="shift_name" class="block text-xs font-semibold text-slate-700 mb-1">Shift Name *</label>
                    <input type="text" id="shift_name" name="name" required placeholder="e.g. Shift Pagi" class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="start_time" class="block text-xs font-semibold text-slate-700 mb-1">Start Time (HH:MM) *</label>
                        <input type="time" id="start_time" name="start_time" required class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600">
                    </div>
                    <div>
                        <label for="end_time" class="block text-xs font-semibold text-slate-700 mb-1">End Time (HH:MM) *</label>
                        <input type="time" id="end_time" name="end_time" required class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600">
                    </div>
                </div>

                <div>
                    <label for="shift_category" class="block text-xs font-semibold text-slate-700 mb-1">Category *</label>
                    <select id="shift_category" name="category" required class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600">
                        <option value="regular">Regular</option>
                        <option value="shifting">Shifting</option>
                    </select>
                </div>

                <div class="space-y-2 pt-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_overnight" value="1" class="w-4 h-4 rounded text-teal-600 focus:ring-teal-500 border-slate-300">
                        <span class="text-xs text-slate-700">Overnight (Spans midnight)</span>
                    </label>

                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" checked class="w-4 h-4 rounded text-teal-600 focus:ring-teal-500 border-slate-300">
                        <span class="text-xs text-slate-700">Active Status</span>
                    </label>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" onclick="document.getElementById('addShiftModal').classList.add('hidden')" class="px-4 py-2 border border-slate-300 text-slate-600 rounded-lg text-xs font-semibold hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-[#0F766E] hover:bg-teal-800 text-white rounded-lg text-xs font-semibold shadow-2xs transition">Save Shift</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Shift Modal --}}
    <div id="editShiftModal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full overflow-hidden shadow-xl border border-slate-200">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                <h3 class="text-sm font-bold text-slate-900">Edit Shift Configuration</h3>
                <button type="button" onclick="document.getElementById('editShiftModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-sm cursor-pointer">✕</button>
            </div>

            <form id="editShiftForm" method="POST" action="" class="p-6 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label for="edit_shift_name" class="block text-xs font-semibold text-slate-700 mb-1">Shift Name *</label>
                    <input type="text" id="edit_shift_name" name="name" required class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="edit_start_time" class="block text-xs font-semibold text-slate-700 mb-1">Start Time *</label>
                        <input type="time" id="edit_start_time" name="start_time" required class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600">
                    </div>
                    <div>
                        <label for="edit_end_time" class="block text-xs font-semibold text-slate-700 mb-1">End Time *</label>
                        <input type="time" id="edit_end_time" name="end_time" required class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600">
                    </div>
                </div>

                <div>
                    <label for="edit_shift_category" class="block text-xs font-semibold text-slate-700 mb-1">Category *</label>
                    <select id="edit_shift_category" name="category" required class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600">
                        <option value="regular">Regular</option>
                        <option value="shifting">Shifting</option>
                    </select>
                </div>

                <div class="space-y-2 pt-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="edit_is_overnight" name="is_overnight" value="1" class="w-4 h-4 rounded text-teal-600 focus:ring-teal-500 border-slate-300">
                        <span class="text-xs text-slate-700">Overnight (Spans midnight)</span>
                    </label>

                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="edit_is_active" name="is_active" value="1" class="w-4 h-4 rounded text-teal-600 focus:ring-teal-500 border-slate-300">
                        <span class="text-xs text-slate-700">Active Status</span>
                    </label>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" onclick="document.getElementById('editShiftModal').classList.add('hidden')" class="px-4 py-2 border border-slate-300 text-slate-600 rounded-lg text-xs font-semibold hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-[#0F766E] hover:bg-teal-800 text-white rounded-lg text-xs font-semibold shadow-2xs transition">Update Shift</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        function openEditShiftModal(data) {
            document.getElementById('editShiftForm').action = data.update_url;
            document.getElementById('edit_shift_name').value = data.name;
            document.getElementById('edit_start_time').value = data.start_time;
            document.getElementById('edit_end_time').value = data.end_time;
            document.getElementById('edit_shift_category').value = data.category;
            document.getElementById('edit_is_overnight').checked = data.is_overnight;
            document.getElementById('edit_is_active').checked = data.is_active;
            document.getElementById('editShiftModal').classList.remove('hidden');
        }
    </script>
    @endpush
@endsection

