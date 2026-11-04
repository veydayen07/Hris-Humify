<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\ReviewStatus;
use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;

class ReportService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function monthlyAttendanceReport(array $filters): array
    {
        $month = $filters['month'] ?? now()->format('Y-m');
        $includePending = filter_var($filters['include_pending'] ?? false, FILTER_VALIDATE_BOOL);

        $employeesQuery = Employee::query()->with(['department', 'position']);

        if (! empty($filters['department_id'])) {
            $employeesQuery->where('department_id', $filters['department_id']);
        }

        if (! empty($filters['employee_id'])) {
            $employeesQuery->where('id', $filters['employee_id']);
        }

        $employees = $employeesQuery->orderBy('full_name')->get();

        $allowedStatuses = [ReviewStatus::Approved];
        if ($includePending) {
            $allowedStatuses[] = ReviewStatus::Pending;
        }

        $attendances = Attendance::query()
            ->where('attendance_date', 'like', "{$month}%")
            ->whereIn('review_status', $allowedStatuses)
            ->whereIn('employee_id', $employees->pluck('id'))
            ->get()
            ->groupBy('employee_id');

        $rows = [];
        $totalPresentDays = 0;
        $totalLateRecords = 0;
        $totalLateMinutes = 0;
        $totalLeaveDays = 0;
        $totalAbsentDays = 0;

        foreach ($employees as $employee) {
            $empAttendances = $attendances->get($employee->id, collect());

            $presentDays = 0;
            $lateRecords = 0;
            $lateMinutes = 0;
            $leaveDays = 0;
            $absentDays = 0;

            foreach ($empAttendances as $att) {
                if (in_array($att->status, [AttendanceStatus::Present, AttendanceStatus::Late, AttendanceStatus::LeftEarly], true)) {
                    $presentDays++;
                }

                if ($att->status === AttendanceStatus::Late) {
                    $lateRecords++;
                    $lateMinutes += (int) $att->late_minutes;
                }

                if (in_array($att->status, [AttendanceStatus::OnLeave, AttendanceStatus::SickLeave, AttendanceStatus::AuthorizedAbsence], true)) {
                    $leaveDays++;
                }

                if ($att->status === AttendanceStatus::Absent) {
                    $absentDays++;
                }
            }

            $totalPresentDays += $presentDays;
            $totalLateRecords += $lateRecords;
            $totalLateMinutes += $lateMinutes;
            $totalLeaveDays += $leaveDays;
            $totalAbsentDays += $absentDays;

            $rows[] = [
                'employee_id' => $employee->id,
                'employee_code' => $employee->employee_code,
                'full_name' => $employee->full_name,
                'department_name' => $employee->department?->name,
                'position_name' => $employee->position?->name,
                'present_days' => $presentDays,
                'late_records' => $lateRecords,
                'late_minutes' => $lateMinutes,
                'leave_days' => $leaveDays,
                'absent_days' => $absentDays,
            ];
        }

        return [
            'month' => $month,
            'summary' => [
                'present_days' => $totalPresentDays,
                'late_records' => $totalLateRecords,
                'late_minutes' => $totalLateMinutes,
                'leave_days' => $totalLeaveDays,
                'absent_days' => $totalAbsentDays,
                'total_employees' => $employees->count(),
            ],
            'employees' => $rows,
        ];
    }

    /**
     * @param  array<string, mixed>  $reportData
     */
    public function exportCsv(array $reportData): string
    {
        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, [
            'Employee Code',
            'Full Name',
            'Department',
            'Position',
            'Present Days',
            'Late Records',
            'Late Minutes',
            'Leave Days',
            'Absent Days',
        ]);

        foreach ($reportData['employees'] as $row) {
            fputcsv($handle, [
                $row['employee_code'],
                $row['full_name'],
                $row['department_name'],
                $row['position_name'],
                $row['present_days'],
                $row['late_records'],
                $row['late_minutes'],
                $row['leave_days'],
                $row['absent_days'],
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv !== false ? $csv : '';
    }
}

