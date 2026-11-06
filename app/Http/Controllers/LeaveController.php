<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LeaveController extends Controller
{
    /**
     * W05: Employee's own leave balance & request history (read-only).
     */
    public function employeeRequests(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();
        $employee = $user->employee;

        $currentYear = (int) date('Y');

        $balances = LeaveBalance::with('leaveType')
            ->where('employee_id', $employee?->id)
            ->where('year', $currentYear)
            ->get();

        $requests = LeaveRequest::with(['leaveType', 'approver'])
            ->where('employee_id', $employee?->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('leave-requests', [
            'user' => $user,
            'employee' => $employee,
            'balances' => $balances,
            'requests' => $requests,
            'currentYear' => $currentYear,
        ]);
    }

    /**
     * W08: Leave Approvals Queue (Manager for direct reports, HR for company-wide).
     */
    public function approvalQueue(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();
        $isHr = $user->isHr() || $user->isSuperAdmin();

        $query = LeaveRequest::with(['employee.department', 'employee.position', 'leaveType', 'approver']);

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

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $requests = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        return view('leave.approval-queue', [
            'user' => $user,
            'requests' => $requests,
            'isHr' => $isHr,
            'search' => $request->input('search'),
            'status' => $request->input('status'),
        ]);
    }

    /**
     * Approve or reject a leave request.
     */
    public function submitDecision(Request $request, int $id): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $isHr = $user->isHr() || $user->isSuperAdmin();

        $leaveRequest = LeaveRequest::with(['employee', 'leaveType'])->findOrFail($id);

        if (! $isHr) {
            if ($leaveRequest->employee?->supervisor_id !== $user->employee_id) {
                abort(403, 'You can only approve requests for your direct subordinates.');
            }
        }

        if (! in_array($leaveRequest->status?->value ?? (string) $leaveRequest->status, ['pending'], true)) {
            return back()->with('error', 'This request has already been processed.');
        }

        $validated = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'rejection_reason' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validated['decision'] === 'rejected' && empty(trim($validated['rejection_reason'] ?? ''))) {
            return back()->with('error', 'Rejection reason is required when rejecting requests.');
        }

        $decision = $validated['decision'];
        $dbStatus = match ($decision) {
            'approved' => 'disetujui',
            'rejected' => 'ditolak',
            default => $decision,
        };

        DB::transaction(function () use ($leaveRequest, $validated, $user, $dbStatus) {
            $leaveRequest->update([
                'status' => $dbStatus,
                'approved_by' => $user->id,
                'approved_at' => now(),
                'rejection_reason' => $validated['decision'] === 'rejected' ? $validated['rejection_reason'] : null,
            ]);

            // If approved and leave type tracks balance, update used_days
            if ($validated['decision'] === 'approved' && $leaveRequest->leaveType?->tracks_balance) {
                $year = (int) date('Y', strtotime($leaveRequest->start_date));
                $balance = LeaveBalance::where('employee_id', $leaveRequest->employee_id)
                    ->where('leave_type_id', $leaveRequest->leave_type_id)
                    ->where('year', $year)
                    ->first();

                if ($balance) {
                    $balance->increment('used_days', $leaveRequest->total_days);
                }
            }
        });

        $actionText = $validated['decision'] === 'approved' ? 'approved' : 'rejected';

        return back()->with('success', "Leave request for {$leaveRequest->employee?->full_name} has been {$actionText}.");
    }
}
