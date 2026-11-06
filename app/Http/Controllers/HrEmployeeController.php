<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class HrEmployeeController extends Controller
{
    /**
     * W09: Employee list.
     */
    public function index(Request $request): View
    {
        $query = Employee::with(['department', 'position', 'supervisor', 'user']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }

        if ($request->filled('employee_status')) {
            $query->where('employee_status', $request->input('employee_status'));
        }

        if ($request->filled('is_shift_worker')) {
            $query->where('is_shift_worker', $request->boolean('is_shift_worker'));
        }

        $employees = $query->orderBy('full_name')->paginate(12)->withQueryString();
        $departments = Department::orderBy('name')->get();

        return view('hr.employees.index', [
            'employees' => $employees,
            'departments' => $departments,
            'search' => $request->input('search'),
            'departmentId' => $request->input('department_id'),
            'status' => $request->input('employee_status'),
            'isShiftWorker' => $request->input('is_shift_worker'),
        ]);
    }

    /**
     * W09: Employee details with 4 tabs.
     */
    public function show(string $id, Request $request): View
    {
        $employee = Employee::with([
            'department',
            'position',
            'supervisor',
            'user',
            'attendances.workShift',
            'leaveBalances.leaveType',
            'leaveRequests.leaveType',
        ])->findOrFail($id);

        $tab = $request->input('tab', 'personal');
        $departments = Department::orderBy('name')->get();
        $positions = Position::orderBy('name')->get();
        $supervisors = Employee::where('id', '!=', $employee->id)->orderBy('full_name')->get();

        return view('hr.employees.show', [
            'employee' => $employee,
            'tab' => $tab,
            'departments' => $departments,
            'positions' => $positions,
            'supervisors' => $supervisors,
        ]);
    }

    /**
     * Create new employee form.
     */
    public function create(): View
    {
        $departments = Department::orderBy('name')->get();
        $positions = Position::orderBy('name')->get();
        $supervisors = Employee::orderBy('full_name')->get();

        return view('hr.employees.create', [
            'departments' => $departments,
            'positions' => $positions,
            'supervisors' => $supervisors,
        ]);
    }

    /**
     * Store newly created employee.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_code' => ['required', 'string', 'max:50', 'unique:employees,employee_code'],
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:100', 'unique:employees,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'gender' => ['required', 'in:male,female,L,P'],
            'department_id' => ['required', 'exists:departments,id'],
            'position_id' => ['required', 'exists:positions,id'],
            'supervisor_id' => ['nullable', 'exists:employees,id'],
            'employment_type' => ['required', 'in:permanent,contract,internship,daily,tetap,kontrak,magang,harian'],
            'employee_status' => ['required', 'in:active,inactive,resigned,long_leave,aktif,nonaktif,resign,cuti_panjang'],
            'is_shift_worker' => ['boolean'],
            'hire_date' => ['required', 'date'],
            'birth_date' => ['nullable', 'date'],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        $employee = Employee::create([
            'id' => (string) Str::uuid(),
            'employee_code' => $validated['employee_code'],
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'gender' => $validated['gender'],
            'department_id' => $validated['department_id'],
            'position_id' => $validated['position_id'],
            'supervisor_id' => $validated['supervisor_id'] ?? null,
            'employment_type' => $validated['employment_type'],
            'employee_status' => $validated['employee_status'],
            'is_shift_worker' => $request->boolean('is_shift_worker'),
            'hire_date' => $validated['hire_date'],
            'birth_date' => $validated['birth_date'] ?? null,
            'birth_place' => $validated['birth_place'] ?? null,
            'address' => $validated['address'] ?? null,
        ]);

        return redirect()->route('hr.employees.show', $employee->id)->with('success', "Employee {$employee->full_name} added successfully.");
    }

    /**
     * Update employee organization and job details.
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        $employee = Employee::findOrFail($id);

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'department_id' => ['required', 'exists:departments,id'],
            'position_id' => ['required', 'exists:positions,id'],
            'supervisor_id' => ['nullable', 'exists:employees,id'],
            'employment_type' => ['required', 'in:permanent,contract,internship,daily,tetap,kontrak,magang,harian'],
            'employee_status' => ['required', 'in:active,inactive,resigned,long_leave,aktif,nonaktif,resign,cuti_panjang'],
            'is_shift_worker' => ['boolean'],
            'hire_date' => ['required', 'date'],
            'resign_date' => ['nullable', 'date'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $employee->update([
            'full_name' => $validated['full_name'],
            'department_id' => $validated['department_id'],
            'position_id' => $validated['position_id'],
            'supervisor_id' => $validated['supervisor_id'] ?? null,
            'employment_type' => $validated['employment_type'],
            'employee_status' => $validated['employee_status'],
            'is_shift_worker' => $request->boolean('is_shift_worker'),
            'hire_date' => $validated['hire_date'],
            'resign_date' => $validated['resign_date'] ?? null,
            'address' => $validated['address'] ?? $employee->address,
            'phone' => $validated['phone'] ?? $employee->phone,
        ]);

        return redirect()->route('hr.employees.show', ['id' => $employee->id, 'tab' => 'job'])->with('success', 'Employee job information updated successfully.');
    }

    /**
     * W10: Update reference face photo.
     */
    public function updateFaceReference(Request $request, string $id): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $employee = Employee::findOrFail($id);

        $request->validate([
            'face_photo' => ['nullable', 'image', 'max:4096'],
            'photo_url' => ['nullable', 'string', 'max:500'],
        ]);

        $photoPath = null;
        if ($request->hasFile('face_photo')) {
            $path = $request->file('face_photo')->store('reference_faces', 'public');
            $photoPath = Storage::url($path);
        } elseif ($request->filled('photo_url')) {
            $photoPath = $request->input('photo_url');
        } else {
            return back()->with('error', 'Please select a reference face photo file first.');
        }

        $employee->update([
            'face_reference_photo_url' => $photoPath,
            'face_registered_at' => now(),
        ]);

        return redirect()->route('hr.employees.show', ['id' => $employee->id, 'tab' => 'face'])->with('success', "Face reference photo for {$employee->full_name} updated successfully.");
    }
}
