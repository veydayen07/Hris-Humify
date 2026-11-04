<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\EmployeeStatus;
use App\Enums\LeaveAttendanceStatus;
use App\Enums\LeaveRequestStatus;
use App\Enums\ReviewStatus;
use App\Enums\UserRole;
use App\Exceptions\ApiException;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LeaveService
{
    public function __construct(
        private CompanyClock $clock,
        private AuditLogger $auditLogger,
    ) {}

    /**
     * @return Collection<int, LeaveType>
     */
    public function getActiveTypes(): Collection
    {
        return LeaveType::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, LeaveType>
     */
    public function getAllTypes(): Collection
    {
        return LeaveType::query()
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createType(User $actor, array $data): LeaveType
    {
        $type = LeaveType::query()->create($data);

        $this->auditLogger->log($actor, 'leave_type.created', $type, $data);

        return $type;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateType(User $actor, LeaveType $type, array $data): LeaveType
    {
        $type->update($data);

        $this->auditLogger->log($actor, 'leave_type.updated', $type, $data);

        return $type->fresh();
    }

    /**
     * @return Collection<int, LeaveBalance>
     */
    public function getBalances(User $user, ?string $employeeId = null, ?int $year = null): Collection
    {
        $targetEmployeeId = $user->employee_id;

        if ($employeeId !== null) {
            if (! $user->isHrOrAbove()) {
                throw ApiException::forbidden('Only HR and Super Admin can view balances of other employees.');
            }
            $targetEmployeeId = $employeeId;
        }

        $query = LeaveBalance::query()
            ->with('leaveType')
            ->where('employee_id', $targetEmployeeId);

        if ($year !== null) {
            $query->where('year', $year);
        } else {
            $query->where('year', (int) $this->clock->attendanceDate()->year);
        }

        return $query->orderBy('leave_type_id')->get();
    }

    public function upsertBalance(
        User $actor,
        string $employeeId,
        int $leaveTypeId,
        int $year,
        float $totalDays,
    ): LeaveBalance {
        $employee = Employee::query()->findOrFail($employeeId);
        $leaveType = LeaveType::query()->findOrFail($leaveTypeId);

        $existing = LeaveBalance::query()
            ->where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('year', $year)
            ->first();

        if ($existing !== null && $totalDays < (float) $existing->used_days) {
            throw ApiException::validation('Total days cannot be less than already used days.', [
                'total_days' => ['Total days cannot be less than already used days ('.$existing->used_days.').'],
            ]);
        }

        $balance = LeaveBalance::query()->updateOrCreate(
            [
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'year' => $year,
            ],
            [
                'total_days' => $totalDays,
                'used_days' => $existing?->used_days ?? 0,
            ],
        );

        $this->auditLogger->log($actor, 'leave_balance.upserted', $balance, [
            'total_days' => $totalDays,
            'year' => $year,
        ]);

        return $balance->fresh('leaveType');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getRequests(User $user, array $filters = []): LengthAwarePaginator
    {
        $scope = $filters['scope'] ?? 'mine';
        $perPage = min(max((int) ($filters['per_page'] ?? 15), 1), 100);

        $query = LeaveRequest::query()->with([
            'employee.department',
            'employee.position',
            'leaveType',
            'approver.employee',
        ]);

        $this->applyScope($query, $user, $scope);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['year'])) {
            $query->whereYear('start_date', (int) $filters['year']);
        }

        if (! empty($filters['type'])) {
            $query->where('leave_type_id', $filters['type']);
        }

        if (! empty($filters['q'])) {
            $q = (string) $filters['q'];
            $query->where(function (Builder $b) use ($q) {
                $b->where('reason', 'like', "%{$q}%")
                    ->orWhereHas('employee', function (Builder $empQuery) use ($q) {
                        $empQuery->where('full_name', 'like', "%{$q}%")
                            ->orWhere('employee_code', 'like', "%{$q}%");
                    });
            });
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function submit(
        User $user,
        int $leaveTypeId,
        string $startDate,
        string $endDate,
        ?string $reason = null,
        ?UploadedFile $attachment = null,
    ): LeaveRequest {
        $employee = $user->employee;

        if ($employee === null || $employee->employee_status !== EmployeeStatus::Active) {
            throw ApiException::forbidden('Employee account is not active.');
        }

        $leaveType = LeaveType::query()->where('is_active', true)->findOrFail($leaveTypeId);

        $start = CarbonImmutable::parse($startDate);
        $end = CarbonImmutable::parse($endDate);

        if ($start->greaterThan($end)) {
            throw ApiException::validation('Start date cannot be after end date.', [
                'end_date' => ['The end date must be a date after or equal to start date.'],
            ]);
        }

        $totalDays = $this->clock->countWorkingDays($start, $end);

        if ($totalDays <= 0) {
            throw ApiException::validation('Selected date range does not contain any working days.', [
                'start_date' => ['No working days found in selected date range.'],
            ]);
        }

        // Check overlapping requests (pending or approved) for this employee
        $overlap = LeaveRequest::query()
            ->where('employee_id', $employee->id)
            ->whereIn('status', [LeaveRequestStatus::Pending, LeaveRequestStatus::Approved])
            ->where(function (Builder $b) use ($start, $end) {
                $b->whereDate('start_date', '<=', $end->toDateString())
                    ->whereDate('end_date', '>=', $start->toDateString());
            })
            ->exists();

        if ($overlap) {
            throw ApiException::requestOverlap();
        }

        if ($leaveType->requires_attachment && $attachment === null) {
            throw ApiException::attachmentRequired();
        }

        $attachmentPath = null;
        if ($attachment !== null) {
            $attachmentPath = $attachment->store("leave_attachments/{$employee->id}", 'local');
        }

        return LeaveRequest::query()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'total_days' => $totalDays,
            'reason' => $reason,
            'attachment_url' => $attachmentPath,
            'status' => LeaveRequestStatus::Pending,
        ])->load(['leaveType', 'employee']);
    }

    public function cancel(User $user, LeaveRequest $request): LeaveRequest
    {
        if ($request->employee_id !== $user->employee_id) {
            throw ApiException::forbidden('You can only cancel your own leave request.');
        }

        if ($request->status !== LeaveRequestStatus::Pending) {
            throw ApiException::alreadyResolved('Cannot cancel a leave request that is not pending.');
        }

        $request->forceFill([
            'status' => LeaveRequestStatus::Canceled,
            'canceled_by' => $user->id,
            'canceled_at' => now(),
        ])->save();

        $this->auditLogger->log($user, 'leave_request.canceled', $request);

        return $request->fresh(['leaveType', 'employee']);
    }

    /**
     * @return array{request: LeaveRequest, skipped_dates: list<string>}
     */
    public function approve(User $actor, LeaveRequest $request, ?string $note = null): array
    {
        $this->assertCanReview($actor, $request);

        if ($request->status !== LeaveRequestStatus::Pending) {
            throw ApiException::alreadyResolved();
        }

        return DB::transaction(function () use ($actor, $request) {
            $leaveType = $request->leaveType;
            $startYear = (int) CarbonImmutable::parse($request->start_date)->year;

            if ($leaveType->tracks_balance) {
                $balance = LeaveBalance::query()
                    ->where('employee_id', $request->employee_id)
                    ->where('leave_type_id', $leaveType->id)
                    ->where('year', $startYear)
                    ->lockForUpdate()
                    ->first();

                if ($balance === null || $balance->remainingDays() < (float) $request->total_days) {
                    throw ApiException::insufficientBalance();
                }

                $balance->increment('used_days', $request->total_days);
            }

            // Determine attendance status based on leave type
            $mappedStatus = match ($leaveType->attendance_status) {
                LeaveAttendanceStatus::SickLeave => AttendanceStatus::SickLeave,
                LeaveAttendanceStatus::AuthorizedAbsence => AttendanceStatus::AuthorizedAbsence,
                default => AttendanceStatus::OnLeave,
            };

            $workingDays = $this->clock->workingDaysBetween(
                CarbonImmutable::parse($request->start_date),
                CarbonImmutable::parse($request->end_date),
            );

            $skippedDates = [];

            foreach ($workingDays as $day) {
                $dateStr = $day->toDateString();

                $existingAttendance = Attendance::query()
                    ->where('employee_id', $request->employee_id)
                    ->whereDate('attendance_date', $dateStr)
                    ->first();

                if ($existingAttendance !== null && $existingAttendance->check_in_time !== null) {
                    $skippedDates[] = $dateStr;

                    continue;
                }

                Attendance::query()->updateOrCreate(
                    [
                        'employee_id' => $request->employee_id,
                        'attendance_date' => $dateStr,
                    ],
                    [
                        'status' => $mappedStatus,
                        'review_status' => ReviewStatus::Approved,
                        'reviewed_by' => null,
                        'reviewed_at' => now(),
                    ],
                );
            }

            $request->forceFill([
                'status' => LeaveRequestStatus::Approved,
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ])->save();

            $this->auditLogger->log($actor, 'leave_request.approved', $request, [
                'skipped_dates' => $skippedDates,
            ]);

            return [
                'request' => $request->fresh(['leaveType', 'employee.department', 'approver.employee']),
                'skipped_dates' => $skippedDates,
            ];
        });
    }

    public function reject(User $actor, LeaveRequest $request, string $reason): LeaveRequest
    {
        $this->assertCanReview($actor, $request);

        if ($request->status !== LeaveRequestStatus::Pending) {
            throw ApiException::alreadyResolved();
        }

        if (trim($reason) === '') {
            throw ApiException::validation('Rejection reason is required.', [
                'reason' => ['The reason field is required.'],
            ]);
        }

        $request->forceFill([
            'status' => LeaveRequestStatus::Rejected,
            'approved_by' => $actor->id,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ])->save();

        $this->auditLogger->log($actor, 'leave_request.rejected', $request, [
            'reason' => $reason,
        ]);

        return $request->fresh(['leaveType', 'employee.department', 'approver.employee']);
    }

    private function assertCanReview(User $actor, LeaveRequest $request): void
    {
        if ($request->employee_id === $actor->employee_id) {
            throw ApiException::forbidden('You cannot review your own leave request.');
        }

        if ($actor->isHrOrAbove()) {
            return;
        }

        if ($actor->role === UserRole::Manager) {
            $isDirectSupervisor = Employee::query()
                ->where('id', $request->employee_id)
                ->where('supervisor_id', $actor->employee_id)
                ->exists();

            if ($isDirectSupervisor) {
                return;
            }
        }

        throw ApiException::forbidden('You do not have permission to review this leave request.');
    }

    /**
     * @param  Builder<LeaveRequest>  $query
     */
    private function applyScope(Builder $query, User $user, string $scope): void
    {
        if ($scope === 'mine') {
            $query->where('employee_id', $user->employee_id);

            return;
        }

        if ($scope === 'team') {
            if (! $user->isManagerOrAbove()) {
                throw ApiException::forbidden('Only managers and HR can use team scope.');
            }

            if ($user->role === UserRole::Manager) {
                $subordinateIds = Employee::query()
                    ->where('supervisor_id', $user->employee_id)
                    ->pluck('id');

                $query->whereIn('employee_id', $subordinateIds);
            }

            return;
        }

        if ($scope === 'company') {
            if (! $user->isHrOrAbove()) {
                throw ApiException::forbidden('Only HR and Super Admin can use company scope.');
            }

            return;
        }

        throw ApiException::validation("Invalid scope '{$scope}'. Allowed: mine, team, company.");
    }
}

