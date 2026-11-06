<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    /**
     * W04: Employee's own attendance history (read-only).
     */
    public function employeeHistory(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();
        $employee = $user->employee;

        $month = $request->input('month', date('m'));
        $year = $request->input('year', date('Y'));
        $status = $request->input('status');

        $query = Attendance::with(['workShift', 'reviewer'])
            ->where('employee_id', $employee?->id)
            ->whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $month);

        if (! empty($status)) {
            $query->where('status', $status);
        }

        $attendances = $query->orderBy('attendance_date', 'desc')->paginate(15)->withQueryString();

        // Summary stats for this month
        $stats = [
            'present' => Attendance::where('employee_id', $employee?->id)
                ->whereYear('attendance_date', $year)
                ->whereMonth('attendance_date', $month)
                ->whereIn('status', ['present', 'hadir'])
                ->count(),
            'late' => Attendance::where('employee_id', $employee?->id)
                ->whereYear('attendance_date', $year)
                ->whereMonth('attendance_date', $month)
                ->whereIn('status', ['late', 'terlambat'])
                ->count(),
            'records' => $attendances->total(),
        ];

        return view('attendance-history', [
            'user' => $user,
            'employee' => $employee,
            'attendances' => $attendances,
            'stats' => $stats,
            'currentMonth' => $month,
            'currentYear' => $year,
            'currentStatus' => $status,
        ]);
    }

    /**
     * W07: Attendance Review Queue (Manager for direct reports, HR for company-wide).
     */
    public function reviewQueue(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();
        $isHr = $user->isHr() || $user->isSuperAdmin();

        $query = Attendance::with(['employee.department', 'employee.position', 'workShift', 'reviewer']);

        if (! $isHr) {
            // Manager: direct reports only
            $subordinateIds = Employee::where('supervisor_id', $user->employee_id)->pluck('id')->all();
            $query->whereIn('employee_id', $subordinateIds);
        }

        // Filters
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('review_status')) {
            $query->where('review_status', $request->input('review_status'));
        }

        if ($request->filled('date')) {
            $query->whereDate('attendance_date', $request->input('date'));
        }

        $attendances = $query->orderBy('attendance_date', 'desc')->paginate(15)->withQueryString();

        return view('attendance.review-queue', [
            'user' => $user,
            'attendances' => $attendances,
            'isHr' => $isHr,
            'search' => $request->input('search'),
            'reviewStatus' => $request->input('review_status'),
            'date' => $request->input('date'),
        ]);
    }

    /**
     * Approve or reject an attendance review item.
     */
    public function submitReview(Request $request, int $id): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $isHr = $user->isHr() || $user->isSuperAdmin();

        $attendance = Attendance::with('employee')->findOrFail($id);

        // Authorization check: if not HR, must be direct manager
        if (! $isHr) {
            if ($attendance->employee?->supervisor_id !== $user->employee_id) {
                abort(403, 'You can only review attendance for your direct subordinates.');
            }
        }

        if (! in_array($attendance->review_status?->value ?? (string) $attendance->review_status, ['pending'], true)) {
            return back()->with('error', 'This attendance record has already been reviewed.');
        }

        $validated = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'review_note' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validated['decision'] === 'rejected' && empty(trim($validated['review_note'] ?? ''))) {
            return back()->with('error', 'Rejection reason is required when rejecting attendance.');
        }

        $decision = $validated['decision'];
        $dbStatus = match ($decision) {
            'approved' => 'disetujui',
            'rejected' => 'ditolak',
            default => $decision,
        };

        $attendance->update([
            'review_status' => $dbStatus,
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
            'review_note' => $validated['review_note'] ?? null,
        ]);

        $actionText = $validated['decision'] === 'approved' ? 'approved' : 'rejected';

        return back()->with('success', "Attendance for {$attendance->employee?->full_name} has been {$actionText}.");
    }

    /**
     * Team Attendance History for Manager.
     */
    public function managerTeamHistory(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();
        $subordinateIds = Employee::where('supervisor_id', $user->employee_id)->pluck('id')->all();

        $query = Attendance::with(['employee.department', 'workShift', 'reviewer'])
            ->whereIn('employee_id', $subordinateIds);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date')) {
            $query->whereDate('attendance_date', $request->input('date'));
        }

        $attendances = $query->orderBy('attendance_date', 'desc')->paginate(15)->withQueryString();

        return view('manager.team-history', [
            'user' => $user,
            'attendances' => $attendances,
            'search' => $request->input('search'),
            'date' => $request->input('date'),
        ]);
    }
}
