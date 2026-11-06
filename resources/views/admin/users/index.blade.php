@extends('layouts.app', [
    'title' => 'Accounts & Roles',
    'active' => 'Accounts & Roles',
    'pageTitle' => 'Account & Role Management'
])

@section('content')
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Account &amp; Role Management</h1>
            <p class="text-xs text-slate-500 mt-1">
                Manage user login credentials, system roles, and account activation status
            </p>
        </div>

        <button
            type="button"
            onclick="document.getElementById('addUserModal').classList.remove('hidden')"
            class="inline-flex items-center gap-2 px-4 py-2 bg-[#0F766E] hover:bg-teal-800 text-white rounded-lg text-xs font-semibold shadow-2xs transition cursor-pointer"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
            <span>Create User Account</span>
        </button>
    </div>

    {{-- Filter Bar --}}
    <div class="bg-white border border-slate-200 rounded-xl p-4 mb-6 shadow-2xs">
        <form method="GET" action="{{ route('admin.users') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
            <div>
                <label for="search" class="block text-[11px] font-semibold text-slate-600 mb-1">Search Account</label>
                <input
                    type="text"
                    id="search"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Username or employee name..."
                    class="w-full px-3 py-1.5 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600"
                >
            </div>

            <div>
                <label for="role" class="block text-[11px] font-semibold text-slate-600 mb-1">Filter Role</label>
                <select name="role" id="role" class="w-full px-3 py-1.5 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600">
                    <option value="">All Roles</option>
                    <option value="employee" {{ in_array($role, ['employee', 'karyawan']) ? 'selected' : '' }}>Employee</option>
                    <option value="manager" {{ $role === 'manager' ? 'selected' : '' }}>Manager</option>
                    <option value="hr_admin" {{ in_array($role, ['hr_admin', 'admin_hr']) ? 'selected' : '' }}>HR Admin</option>
                    <option value="super_admin" {{ $role === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="w-full py-1.5 px-3 bg-[#0F766E] hover:bg-teal-800 text-white rounded-lg text-xs font-semibold shadow-2xs transition cursor-pointer">
                    Apply Filter
                </button>
                <a href="{{ route('admin.users') }}" class="py-1.5 px-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-xs font-medium text-center">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- Users Table --}}
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-2xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-400 font-semibold border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3.5">Username</th>
                        <th class="px-6 py-3.5">Linked Employee</th>
                        <th class="px-6 py-3.5">Department</th>
                        <th class="px-6 py-3.5">Role</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5">Last Login</th>
                        <th class="px-6 py-3.5 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $usr)
                        @php
                            $roleVal = is_object($usr->role) ? $usr->role->value : (string) $usr->role;

                            $roleBadge = match ($roleVal) {
                                'super_admin' => 'bg-purple-50 text-purple-700 border-purple-200',
                                'hr_admin', 'admin_hr' => 'bg-blue-50 text-blue-700 border-blue-200',
                                'manager' => 'bg-amber-50 text-amber-700 border-amber-200',
                                default => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-6 py-3.5 font-semibold font-mono text-slate-900">
                                {{ $usr->username }}
                            </td>
                            <td class="px-6 py-3.5">
                                <div class="font-medium text-slate-800">{{ $usr->employee?->full_name ?? 'System User' }}</div>
                                <div class="text-[10px] text-slate-400">{{ $usr->employee?->employee_code ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-3.5 text-slate-600">
                                {{ $usr->employee?->department?->name ?? '-' }}
                            </td>
                            <td class="px-6 py-3.5">
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold border {{ $roleBadge }}">
                                    {{ $usr->getRoleLabelAttribute() }}
                                </span>
                            </td>
                            <td class="px-6 py-3.5">
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-medium border {{ $usr->is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' }}">
                                    {{ $usr->is_active ? 'Active' : 'Disabled' }}
                                </span>
                            </td>
                            <td class="px-6 py-3.5 text-slate-500">
                                {{ $usr->last_login_at ? \Carbon\Carbon::parse($usr->last_login_at)->translatedFormat('d M Y, H:i') : 'Never' }}
                            </td>
                            <td class="px-6 py-3.5 text-right">
                                <button
                                    type="button"
                                    onclick="openEditUserModal({{ json_encode([
                                        'id' => $usr->id,
                                        'username' => $usr->username,
                                        'role' => $roleVal,
                                        'is_active' => (bool) $usr->is_active,
                                        'update_url' => route('admin.users.update', $usr->id),
                                    ]) }})"
                                    class="px-2.5 py-1 bg-slate-100 hover:bg-teal-50 hover:text-teal-700 rounded text-xs font-semibold cursor-pointer"
                                >
                                    Edit Role
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-xs text-slate-400">
                                No user accounts match the search criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
                {{ $users->links() }}
            </div>
        @endif
    </div>

    {{-- Add User Modal --}}
    <div id="addUserModal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full overflow-hidden shadow-xl border border-slate-200">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                <h3 class="text-sm font-bold text-slate-900">Create User Login Account</h3>
                <button type="button" onclick="document.getElementById('addUserModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-sm cursor-pointer">✕</button>
            </div>

            <form method="POST" action="{{ route('admin.users.store') }}" class="p-6 space-y-4">
                @csrf
                <div>
                    <label for="new_employee_id" class="block text-xs font-semibold text-slate-700 mb-1">Select Employee *</label>
                    <select id="new_employee_id" name="employee_id" required class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600">
                        <option value="">-- Choose employee --</option>
                        @foreach ($availableEmployees as $aEmp)
                            <option value="{{ $aEmp->id }}">{{ $aEmp->full_name }} ({{ $aEmp->employee_code }})</option>
                        @endforeach
                    </select>
                    @if ($availableEmployees->isEmpty())
                        <p class="text-[11px] text-amber-600 mt-1">All current employees already have a user account.</p>
                    @endif
                </div>

                <div>
                    <label for="new_username" class="block text-xs font-semibold text-slate-700 mb-1">Username *</label>
                    <input type="text" id="new_username" name="username" required placeholder="e.g. j.doe" class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600">
                </div>

                <div>
                    <label for="new_password" class="block text-xs font-semibold text-slate-700 mb-1">Initial Password *</label>
                    <input type="password" id="new_password" name="password" minlength="6" required placeholder="Min 6 characters" class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600">
                </div>

                <div>
                    <label for="new_role" class="block text-xs font-semibold text-slate-700 mb-1">System Role *</label>
                    <select id="new_role" name="role" required class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600">
                        <option value="employee">Employee</option>
                        <option value="manager">Manager</option>
                        <option value="hr_admin">HR Admin</option>
                        <option value="super_admin">Super Admin</option>
                    </select>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" onclick="document.getElementById('addUserModal').classList.add('hidden')" class="px-4 py-2 border border-slate-300 text-slate-600 rounded-lg text-xs font-semibold hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-[#0F766E] hover:bg-teal-800 text-white rounded-lg text-xs font-semibold shadow-2xs transition">Create Account</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit User Modal --}}
    <div id="editUserModal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full overflow-hidden shadow-xl border border-slate-200">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                <h3 class="text-sm font-bold text-slate-900">Edit Account &amp; Role</h3>
                <button type="button" onclick="document.getElementById('editUserModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-sm cursor-pointer">✕</button>
            </div>

            <form id="editUserForm" method="POST" action="" class="p-6 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <span class="text-slate-400 block text-[11px]">Username</span>
                    <span id="edit_username_display" class="font-bold text-slate-900 text-sm font-mono">-</span>
                </div>

                <div>
                    <label for="edit_role" class="block text-xs font-semibold text-slate-700 mb-1">Assigned Role *</label>
                    <select id="edit_role" name="role" required class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600">
                        <option value="employee">Employee</option>
                        <option value="manager">Manager</option>
                        <option value="hr_admin">HR Admin</option>
                        <option value="super_admin">Super Admin</option>
                    </select>
                </div>

                <div>
                    <label for="edit_password" class="block text-xs font-semibold text-slate-700 mb-1">Reset Password (Optional)</label>
                    <input type="password" id="edit_password" name="new_password" placeholder="Leave blank if unchanged" class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600">
                </div>

                <div class="pt-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="edit_is_active" name="is_active" value="1" class="w-4 h-4 rounded text-teal-600 focus:ring-teal-500 border-slate-300">
                        <span class="text-xs font-medium text-slate-700">Active Account (Can Login)</span>
                    </label>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" onclick="document.getElementById('editUserModal').classList.add('hidden')" class="px-4 py-2 border border-slate-300 text-slate-600 rounded-lg text-xs font-semibold hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-[#0F766E] hover:bg-teal-800 text-white rounded-lg text-xs font-semibold shadow-2xs transition">Update Account</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        function openEditUserModal(data) {
            document.getElementById('editUserForm').action = data.update_url;
            document.getElementById('edit_username_display').textContent = data.username;

            let roleVal = data.role;
            if (roleVal === 'karyawan') roleVal = 'employee';
            if (roleVal === 'admin_hr') roleVal = 'hr_admin';
            document.getElementById('edit_role').value = roleVal;

            document.getElementById('edit_is_active').checked = data.is_active;
            document.getElementById('edit_password').value = '';
            document.getElementById('editUserModal').classList.remove('hidden');
        }
    </script>
    @endpush
@endsection

