<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\EmployeeStatus;
use App\Enums\LeaveRequestStatus;
use App\Enums\ReviewStatus;
use App\Enums\UserRole;
use App\Exceptions\ApiException;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\User;

class DashboardService
{
    public function __construct(
        private CompanyClock $clock,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function employee(User $user): array
    {
        $employee = $user->employee;
        $currentYear = (int) $this->clock->attendanceDate()->year;

        $balances = LeaveBalance::query()
            ->with('leaveType')
            ->where('employee_id', $employee->id)
            ->where('year', $currentYear)
            ->get()
            ->map(fn (LeaveBalance $b) => [
                'leave_type_id' => $b->leave_type_id,
                'name' => $b->leaveType->name,
                'total_days' => (float) $b->total_days,
                'used_days' => (float) $b->used_days,
                'remaining_days' => $b->remainingDays(),
            ]);

        $pendingRequestsCount = LeaveRequest::query()
            ->where('employee_id', $employee->id)
            ->where('status', LeaveRequestStatus::Pending)
            ->count();

        $recentAttendances = Attendance::query()
            ->with(['shift', 'checkInWorkLocation'])
            ->where('employee_id', $employee->id)
            ->orderByDesc('attendance_date')
            ->limit(5)
            ->get();

        return [
            'leave_balances' => $balances,
            'pending_requests_count' => $pendingRequestsCount,
            'recent_attendances' => $recentAttendances,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function team(User $user): array
    {
        if (! $user->isManagerOrAbove()) {
            throw ApiException::forbidden('Only managers and HR can access team dashboard.');
        }

        $subordinates = Employee::query()
            ->where('supervisor_id', $user->employee_id)
            ->where('employee_status', EmployeeStatus::Active)
            ->get();

        $subordinateIds = $subordinates->pluck('id');
        $todayDate = $this->clock->attendanceDate()->toDateString();

        $pendingAttendanceReviewsCount = Attendance::query()
            ->whereIn('employee_id', $subordinateIds)
            ->where('review_status', ReviewStatus::Pending)
            ->count();

        $pendingLeaveRequestsCount = LeaveRequest::query()
            ->whereIn('employee_id', $subordinateIds)
            ->where('status', LeaveRequestStatus::Pending)
            ->count();

        $todayAttendances = Attendance::query()
            ->whereIn('employee_id', $subordinateIds)
            ->whereDate('attendance_date', $todayDate)
            ->get()
            ->keyBy('employee_id');

        $presentCount = 0;
        $lateCount = 0;
        $absentCount = 0;

        foreach ($subordinates as $sub) {
            $att = $todayAttendances->get($sub->id);
            if ($att === null) {
                $absentCount++;
            } elseif ($att->status === AttendanceStatus::Late) {
                $presentCount++;
                $lateCount++;
            } elseif (in_array($att->status, [AttendanceStatus::Present, AttendanceStatus::LeftEarly], true)) {
                $presentCount++;
            } else {
                $absentCount++;
            }
        }

        return [
            'team_members_count' => $subordinates->count(),
            'pending_attendance_reviews_count' => $pendingAttendanceReviewsCount,
            'pending_leave_requests_count' => $pendingLeaveRequestsCount,
            'today_summary' => [
                'date' => $todayDate,
                'present' => $presentCount,
                'late' => $lateCount,
                'absent' => $absentCount,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function hr(User $user): array
    {
        if (! $user->isHrOrAbove()) {
            throw ApiException::forbidden('Only HR and Super Admin can access HR dashboard.');
        }

        $pendingAttendanceCount = Attendance::query()
            ->where('review_status', ReviewStatus::Pending)
            ->count();

        $pendingLeaveCount = LeaveRequest::query()
            ->where('status', LeaveRequestStatus::Pending)
            ->count();

        return [
            'pending_attendance_reviews_count' => $pendingAttendanceCount,
            'pending_leave_requests_count' => $pendingLeaveCount,
        ];
    }
}

