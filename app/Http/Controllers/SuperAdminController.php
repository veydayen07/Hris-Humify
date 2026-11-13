<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SuperAdminController extends Controller
{
    /**
     * W14: User Account and Role Management.
     */
    public function users(Request $request): View
    {
        $query = User::with(['employee.department', 'employee.position']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                    ->orWhereHas('employee', function ($eq) use ($search) {
                        $eq->where('full_name', 'like', "%{$search}%")
                            ->orWhere('employee_code', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(12)->withQueryString();

        // Employees who do not have an account yet
        $availableEmployees = Employee::doesntHave('user')->orderBy('full_name')->get();

        return view('admin.users.index', [
            'users' => $users,
            'availableEmployees' => $availableEmployees,
            'search' => $request->input('search'),
            'role' => $request->input('role'),
        ]);
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,id', 'unique:users,employee_id'],
            'username' => ['required', 'string', 'min:3', 'max:50', 'unique:users,username'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', 'in:employee,karyawan,manager,hr_admin,admin_hr,super_admin'],
        ]);

        $passwordHash = Hash::make($validated['password']);

        $role = $validated['role'];
        if ($role === 'employee') {
            $role = 'karyawan';
        }
        if ($role === 'hr_admin') {
            $role = 'admin_hr';
        }

        User::create([
            'id' => (string) Str::uuid(),
            'employee_id' => $validated['employee_id'],
            'username' => $validated['username'],
            'password_hash' => $passwordHash,
            'role' => $role,
            'is_active' => true,
        ]);

        return redirect()->route('admin.users')->with('success', "Account for {$validated['username']} created successfully.");
    }

    public function updateUser(Request $request, string $id): RedirectResponse
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'username' => ['nullable', 'string', 'min:3', 'max:50', 'unique:users,username,'.$user->id],
            'role' => ['required', 'in:employee,karyawan,manager,hr_admin,admin_hr,super_admin'],
            'is_active' => ['boolean'],
            'new_password' => ['nullable', 'string', 'min:6'],
        ]);

        // Guard: prevent user from demoting or disabling self
        if ($user->id === $currentUser->id) {
            if (! $request->boolean('is_active')) {
                return back()->with('error', 'You cannot deactivate your own account.');
            }
            if ($currentUser->isSuperAdmin() && $validated['role'] !== 'super_admin') {
                return back()->with('error', 'You cannot remove the Super Admin role from your own account.');
            }
        }

        $role = $validated['role'];
        if ($role === 'employee') {
            $role = 'karyawan';
        }
        if ($role === 'hr_admin') {
            $role = 'admin_hr';
        }

        $data = [
            'role' => $role,
            'is_active' => $request->boolean('is_active'),
        ];

        if (! empty($validated['username'])) {
            $data['username'] = $validated['username'];
        }

        if (! empty($validated['new_password'])) {
            $hashed = Hash::make($validated['new_password']);
            $data['password'] = $hashed;
            $data['password_hash'] = $hashed;
        }

        $user->update($data);

        return redirect()->route('admin.users')->with('success', "Account {$user->username} updated successfully.");
    }

    public function destroyUser(string $id): RedirectResponse
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();
        $user = User::findOrFail($id);

        // Guard: prevent self-deletion
        if ($user->id === $currentUser->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $username = $user->username;
        $user->delete();

        return redirect()->route('admin.users')->with('success', "Account {$username} has been successfully deleted.");
    }
}
