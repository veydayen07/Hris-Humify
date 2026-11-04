<?php

namespace App\Services;

use App\Contracts\FaceVerifier;
use App\Enums\AttendanceAttemptOutcome;
use App\Enums\AttendanceAttemptType;
use App\Enums\AttendanceStatus;
use App\Enums\EmployeeStatus;
use App\Enums\ReviewStatus;
use App\Enums\ShiftCategory;
use App\Enums\UserRole;
use App\Exceptions\ApiException;
use App\Models\Attendance;
use App\Models\AttendanceAttempt;
use App\Models\Employee;
use App\Models\Setting;
use App\Models\User;
use App\Models\WorkShift;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator as PaginatorInstance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class AttendanceService
{
    public function __construct(
        private CompanyClock $clock,
        private GeofenceService $geofence,
        private FaceVerifier $faceVerifier,
        private AuditLogger $auditLogger,
    ) {}

    /**
     * @return array{work_category: string, available_shifts: mixed, record: ?Attendance, face_registered: bool, next_action: string, attendance_date: string}
     */
    public function today(User $user): array
    {
        $employee = $this->requireActiveEmployee($user);
        $date = $this->clock->attendanceDate();
        $record = Attendance::query()
            ->with(['shift', 'checkInWorkLocation', 'checkOutWorkLocation'])
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', $date->toDateString())
            ->first();

        $shifts = $this->availableShifts($employee);

        return [
            'work_category' => $employee->workCategoryValue(),
            'available_shifts' => $shifts,
            'record' => $record,
            'face_registered' => $employee->isFaceRegistered(),
            'next_action' => $this->nextAction($employee, $record),
            'attendance_date' => $date->toDateString(),
        ];
    }

    /**
     * @return Collection<int, WorkShift>
     */
    public function availableShifts(Employee $employee): Collection
    {
        return WorkShift::query()
            ->where('is_active', true)
            ->where('category', $employee->workCategory()->value)
            ->orderBy('id')
            ->get();
    }

    public function checkIn(
        User $user,
        int $shiftId,
        UploadedFile $photo,
        float $latitude,
        float $longitude,
        ?string $idempotencyKey = null,
    ): Attendance {
        $employee = $this->requireActiveEmployee($user);
        $this->ensureFaceRegistered($employee);

        $shift = WorkShift::query()->findOrFail($shiftId);
        $this->assertShiftAllowed($employee, $shift);

        $now = $this->clock->now();
        $attendanceDate = $this->clock->attendanceDate($now)->toDateString();

        if ($idempotencyKey) {
            $existingByKey = Attendance::query()
                ->where('employee_id', $employee->id)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existingByKey) {
                return $existingByKey->load(['shift', 'checkInWorkLocation', 'checkOutWorkLocation']);
            }
        }

        $existing = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', $attendanceDate)
            ->whereNotNull('check_in_time')
            ->first();

        if ($existing) {
            throw ApiException::alreadyCheckedIn(
                (new \App\Http\Resources\AttendanceResource($existing->load(['shift', 'checkInWorkLocation'])))->resolve()
            );
        }

        $geo = $this->geofence->validate($latitude, $longitude);
        if (! $geo['valid']) {
            $this->recordAttempt(
                $employee,
                AttendanceAttemptType::CheckIn,
                AttendanceAttemptOutcome::Failed,
                'OUTSIDE_PERMITTED_AREA',
                null,
                $geo['distance_m'],
                $geo['location']?->id,
            );

            throw ApiException::outsidePermittedArea($geo['distance_m']);
        }

        $attemptPath = $photo->store("attendance/{$employee->id}/attempts", 'local');

        try {
            $absoluteReference = Storage::disk('local')->path($employee->face_reference_photo_url);
            $absoluteAttempt = Storage::disk('local')->path($attemptPath);
            $face = $this->faceVerifier->verify($absoluteReference, $absoluteAttempt);
        } catch (Throwable $e) {
            Storage::disk('local')->delete($attemptPath);
            throw ApiException::faceServiceUnavailable();
        }

        $threshold = Setting::faceMatchThreshold();
        $matched = $face->matched && $face->score >= $threshold;

        if (! $matched) {
            Storage::disk('local')->delete($attemptPath);
            $this->recordAttempt(
                $employee,
                AttendanceAttemptType::CheckIn,
                AttendanceAttemptOutcome::Failed,
                'FACE_MISMATCH',
                $face->score,
                $geo['distance_m'],
                $geo['location']?->id,
            );

            throw ApiException::faceMismatch($face->score);
        }

        $photoPath = $photo->store("attendance/{$employee->id}/photos", 'local');
        Storage::disk('local')->delete($attemptPath);

        $lateMinutes = $this->calculateLateMinutes($shift, $attendanceDate, $now);
        $status = $lateMinutes > 0 ? AttendanceStatus::Late : AttendanceStatus::Present;

        return DB::transaction(function () use (
            $employee,
            $shift,
            $attendanceDate,
            $now,
            $latitude,
            $longitude,
            $photoPath,
            $face,
            $geo,
            $lateMinutes,
            $status,
            $idempotencyKey,
        ) {
            $attendance = Attendance::query()->updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'attendance_date' => $attendanceDate,
                ],
                [
                    'shift_id' => $shift->id,
                    'check_in_time' => $now,
                    'check_in_lat' => $latitude,
                    'check_in_lng' => $longitude,
                    'check_in_photo_url' => $photoPath,
                    'check_in_face_verified' => true,
                    'check_in_face_score' => $face->score,
                    'check_in_work_location_id' => $geo['location']->id,
                    'check_in_geofence_valid' => true,
                    'check_in_distance_m' => $geo['distance_m'],
                    'status' => $status,
                    'late_minutes' => $lateMinutes,
                    'review_status' => ReviewStatus::Pending,
                    'idempotency_key' => $idempotencyKey,
                ],
            );

            $this->recordAttempt(
                $employee,
                AttendanceAttemptType::CheckIn,
                AttendanceAttemptOutcome::Success,
                null,
                $face->score,
                $geo['distance_m'],
                $geo['location']->id,
            );

            return $attendance->fresh(['shift', 'checkInWorkLocation', 'checkOutWorkLocation']);
        });
    }

    public function checkOut(
        User $user,
        UploadedFile $photo,
        float $latitude,
        float $longitude,
    ): Attendance {
        $employee = $this->requireActiveEmployee($user);
        $this->ensureFaceRegistered($employee);

        $now = $this->clock->now();
        $attendance = Attendance::query()
            ->with('shift')
            ->where('employee_id', $employee->id)
            ->whereNotNull('check_in_time')
            ->whereNull('check_out_time')
            ->where('check_in_time', '>=', $now->subDay())
            ->orderByDesc('check_in_time')
            ->first();

        if ($attendance === null) {
            $today = Attendance::query()
                ->where('employee_id', $employee->id)
                ->whereDate('attendance_date', $this->clock->attendanceDate($now)->toDateString())
                ->whereNotNull('check_out_time')
                ->first();

            if ($today) {
                throw ApiException::alreadyCheckedOut();
            }

            throw ApiException::notCheckedIn();
        }

        $geo = $this->geofence->validate($latitude, $longitude);
        if (! $geo['valid']) {
            $this->recordAttempt(
                $employee,
                AttendanceAttemptType::CheckOut,
                AttendanceAttemptOutcome::Failed,
                'OUTSIDE_PERMITTED_AREA',
                null,
                $geo['distance_m'],
                $geo['location']?->id,
            );

            throw ApiException::outsidePermittedArea($geo['distance_m']);
        }

        $attemptPath = $photo->store("attendance/{$employee->id}/attempts", 'local');

        try {
            $absoluteReference = Storage::disk('local')->path($employee->face_reference_photo_url);
            $absoluteAttempt = Storage::disk('local')->path($attemptPath);
            $face = $this->faceVerifier->verify($absoluteReference, $absoluteAttempt);
        } catch (Throwable $e) {
            Storage::disk('local')->delete($attemptPath);
            throw ApiException::faceServiceUnavailable();
        }

        $threshold = Setting::faceMatchThreshold();
        $matched = $face->matched && $face->score >= $threshold;

        if (! $matched) {
            Storage::disk('local')->delete($attemptPath);
            $this->recordAttempt(
                $employee,
                AttendanceAttemptType::CheckOut,
                AttendanceAttemptOutcome::Failed,
                'FACE_MISMATCH',
                $face->score,
                $geo['distance_m'],
                $geo['location']?->id,
            );

            throw ApiException::faceMismatch($face->score);
        }

        $photoPath = $photo->store("attendance/{$employee->id}/photos", 'local');
        Storage::disk('local')->delete($attemptPath);

        $earlyLeaveMinutes = 0;
        $status = $attendance->status;

        if ($attendance->shift) {
            $earlyLeaveMinutes = $this->calculateEarlyLeaveMinutes(
                $attendance->shift,
                CarbonImmutable::parse($attendance->attendance_date),
                $now,
            );

            if ($earlyLeaveMinutes > 0 && $attendance->late_minutes === 0 && $status === AttendanceStatus::Present) {
                $status = AttendanceStatus::LeftEarly;
            }
        }

        $workMinutes = (int) max(0, $attendance->check_in_time->diffInMinutes($now));

        return DB::transaction(function () use (
            $attendance,
            $employee,
            $now,
            $latitude,
            $longitude,
            $photoPath,
            $face,
            $geo,
            $earlyLeaveMinutes,
            $status,
            $workMinutes,
        ) {
            $attendance->fill([
                'check_out_time' => $now,
                'check_out_lat' => $latitude,
                'check_out_lng' => $longitude,
                'check_out_photo_url' => $photoPath,
                'check_out_face_verified' => true,
                'check_out_face_score' => $face->score,
                'check_out_work_location_id' => $geo['location']->id,
                'check_out_geofence_valid' => true,
                'check_out_distance_m' => $geo['distance_m'],
                'early_leave_minutes' => $earlyLeaveMinutes,
                'work_minutes' => $workMinutes,
                'status' => $status,
            ])->save();

            $this->recordAttempt(
                $employee,
                AttendanceAttemptType::CheckOut,
                AttendanceAttemptOutcome::Success,
                null,
                $face->score,
                $geo['distance_m'],
                $geo['location']->id,
            );

            return $attendance->fresh(['shift', 'checkInWorkLocation', 'checkOutWorkLocation']);
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getRecords(User $user, array $filters = []): LengthAwarePaginator
    {
        $scope = $filters['scope'] ?? 'mine';
        $perPage = min(max((int) ($filters['per_page'] ?? 15), 1), 100);
        $page = (int) ($filters['page'] ?? 1);

        $query = Attendance::query()->with([
            'employee.department',
            'employee.position',
            'shift',
            'reviewer.employee',
            'checkInWorkLocation',
            'checkOutWorkLocation',
        ]);

        $this->applyScope($query, $user, $scope);

        if (! empty($filters['month'])) {
            $month = (string) $filters['month']; // YYYY-MM
            $query->where('attendance_date', 'like', "{$month}%");
        }

        if (! empty($filters['view'])) {
            if ($filters['view'] === 'absences') {
                $query->whereIn('status', [AttendanceStatus::Absent, AttendanceStatus::DayOff]);
            } elseif ($filters['view'] === 'recorded') {
                $query->whereNotIn('status', [AttendanceStatus::Absent, AttendanceStatus::DayOff]);
            }
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['review_status'])) {
            $query->where('review_status', $filters['review_status']);
        }

        if (! empty($filters['q'])) {
            $q = (string) $filters['q'];
            $query->whereHas('employee', function (Builder $b) use ($q) {
                $b->where('full_name', 'like', "%{$q}%")
                    ->orWhere('employee_code', 'like', "%{$q}%");
            });
        }

        // If scope is mine and month is provided, synthesize weekend day_off rows
        if ($scope === 'mine' && ! empty($filters['month'])) {
            $records = $query->orderBy('attendance_date', 'desc')->get();
            $merged = $this->mergeSyntheticDaysOff($user->employee, $records, (string) $filters['month'], $filters);

            $slice = $merged->slice(($page - 1) * $perPage, $perPage)->values();

            return new PaginatorInstance(
                $slice,
                $merged->count(),
                $perPage,
                $page,
                ['path' => request()->url(), 'query' => request()->query()],
            );
        }

        return $query->orderByDesc('attendance_date')->paginate($perPage);
    }

    public function getRecord(User $user, int $id): Attendance
    {
        $attendance = Attendance::query()
            ->with([
                'employee.department',
                'employee.position',
                'shift',
                'reviewer.employee',
                'checkInWorkLocation',
                'checkOutWorkLocation',
            ])
            ->findOrFail($id);

        $this->assertCanViewRecord($user, $attendance);

        return $attendance;
    }

    public function approve(User $actor, Attendance $attendance, ?string $note = null): Attendance
    {
        $this->assertCanReview($actor, $attendance);

        if ($attendance->isResolved()) {
            throw ApiException::alreadyResolved();
        }

        $attendance->forceFill([
            'review_status' => ReviewStatus::Approved,
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
            'review_note' => $note,
        ])->save();

        $this->auditLogger->log($actor, 'attendance.approved', $attendance, [
            'note' => $note,
        ]);

        return $attendance->fresh(['employee', 'shift', 'reviewer.employee']);
    }

    public function reject(User $actor, Attendance $attendance, string $reason): Attendance
    {
        $this->assertCanReview($actor, $attendance);

        if ($attendance->isResolved()) {
            throw ApiException::alreadyResolved();
        }

        if (trim($reason) === '') {
            throw ApiException::validation('Rejection reason is required.', [
                'reason' => ['The reason field is required.'],
            ]);
        }

        $attendance->forceFill([
            'review_status' => ReviewStatus::Rejected,
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
            'review_note' => $reason,
        ])->save();

        $this->auditLogger->log($actor, 'attendance.rejected', $attendance, [
            'reason' => $reason,
        ]);

        return $attendance->fresh(['employee', 'shift', 'reviewer.employee']);
    }

    public function closeDay(?CarbonImmutable $targetDate = null): int
    {
        $date = $targetDate ?? $this->clock->attendanceDate()->subDay();

        // If date is weekend, skip
        if ($this->clock->isWeekend($date)) {
            return 0;
        }

        $dateStr = $date->toDateString();

        $activeEmployees = Employee::query()
            ->where('employee_status', EmployeeStatus::Active)
            ->whereDate('hire_date', '<=', $dateStr)
            ->where(function ($q) use ($dateStr) {
                $q->whereNull('resign_date')->orWhereDate('resign_date', '>=', $dateStr);
            })
            ->get();

        $createdCount = 0;

        foreach ($activeEmployees as $employee) {
            $hasAttendance = Attendance::query()
                ->where('employee_id', $employee->id)
                ->whereDate('attendance_date', $dateStr)
                ->exists();

            if (! $hasAttendance) {
                Attendance::query()->create([
                    'employee_id' => $employee->id,
                    'attendance_date' => $dateStr,
                    'status' => AttendanceStatus::Absent,
                    'review_status' => ReviewStatus::Pending,
                ]);

                $createdCount++;
            }
        }

        return $createdCount;
    }

    public function requireActiveEmployee(User $user): Employee
    {
        $employee = $user->employee;

        if ($employee === null || $employee->employee_status !== EmployeeStatus::Active) {
            throw ApiException::forbidden('Employee account is not active.');
        }

        return $employee;
    }

    private function ensureFaceRegistered(Employee $employee): void
    {
        if (! $employee->isFaceRegistered()) {
            throw ApiException::faceNotRegistered();
        }
    }

    private function assertShiftAllowed(Employee $employee, WorkShift $shift): void
    {
        if (! $shift->is_active) {
            throw ApiException::shiftNotAllowed();
        }

        $expected = $employee->is_shift_worker ? ShiftCategory::Shifting : ShiftCategory::Regular;

        if ($shift->category !== $expected) {
            throw ApiException::shiftNotAllowed();
        }

        if (! $shift->isConfigured()) {
            throw ApiException::shiftNotConfigured();
        }
    }

    private function calculateLateMinutes(WorkShift $shift, string $attendanceDate, CarbonImmutable $checkInAt): int
    {
        $start = $this->clock->shiftDateTime(CarbonImmutable::parse($attendanceDate), (string) $shift->start_time);

        if ($checkInAt->lessThanOrEqualTo($start)) {
            return 0;
        }

        return (int) ceil($start->diffInSeconds($checkInAt) / 60);
    }

    private function calculateEarlyLeaveMinutes(WorkShift $shift, CarbonImmutable $attendanceDate, CarbonImmutable $checkOutAt): int
    {
        $end = $this->clock->shiftDateTime(
            $attendanceDate,
            (string) $shift->end_time,
            (bool) $shift->is_overnight,
        );

        if ($checkOutAt->greaterThanOrEqualTo($end)) {
            return 0;
        }

        return (int) ceil($checkOutAt->diffInSeconds($end) / 60);
    }

    private function nextAction(Employee $employee, ?Attendance $record): string
    {
        if (! $employee->isFaceRegistered()) {
            return 'register_face';
        }

        if ($record === null || $record->check_in_time === null) {
            return 'check_in';
        }

        if ($record->check_out_time === null) {
            return 'check_out';
        }

        return 'done';
    }

    private function recordAttempt(
        Employee $employee,
        AttendanceAttemptType $type,
        AttendanceAttemptOutcome $outcome,
        ?string $failureCode,
        ?float $faceScore,
        ?int $distanceM,
        ?int $workLocationId,
    ): void {
        AttendanceAttempt::query()->create([
            'employee_id' => $employee->id,
            'type' => $type,
            'outcome' => $outcome,
            'failure_code' => $failureCode,
            'face_score' => $faceScore,
            'distance_m' => $distanceM,
            'work_location_id' => $workLocationId,
            'created_at' => now(),
        ]);
    }

    private function assertCanViewRecord(User $user, Attendance $attendance): void
    {
        if ($user->isHrOrAbove()) {
            return;
        }

        if ($user->employee_id === $attendance->employee_id) {
            return;
        }

        if ($user->role === UserRole::Manager) {
            $isDirectSupervisor = Employee::query()
                ->where('id', $attendance->employee_id)
                ->where('supervisor_id', $user->employee_id)
                ->exists();

            if ($isDirectSupervisor) {
                return;
            }
        }

        throw ApiException::forbidden('You do not have permission to view this attendance record.');
    }

    private function assertCanReview(User $actor, Attendance $attendance): void
    {
        if ($attendance->employee_id === $actor->employee_id) {
            throw ApiException::forbidden('You cannot review your own attendance record.');
        }

        if ($actor->isHrOrAbove()) {
            return;
        }

        if ($actor->role === UserRole::Manager) {
            $isDirectSupervisor = Employee::query()
                ->where('id', $attendance->employee_id)
                ->where('supervisor_id', $actor->employee_id)
                ->exists();

            if ($isDirectSupervisor) {
                return;
            }
        }

        throw ApiException::forbidden('You do not have permission to review this attendance record.');
    }

    /**
     * @param  Builder<Attendance>  $query
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

    /**
     * @param  Collection<int, Attendance>  $records
     * @param  array<string, mixed>  $filters
     * @return \Illuminate\Support\Collection<int, Attendance>
     */
    private function mergeSyntheticDaysOff(Employee $employee, Collection $records, string $month, array $filters): \Illuminate\Support\Collection
    {
        $existingByDate = $records->keyBy(fn ($r) => $r->attendance_date->toDateString());
        $startOfMonth = CarbonImmutable::parse("{$month}-01", $this->clock->timezone());
        $endOfMonth = $startOfMonth->endOfMonth();
        $period = CarbonPeriod::create($startOfMonth, $endOfMonth);

        $merged = collect();

        foreach ($period as $date) {
            $immutable = CarbonImmutable::instance($date);
            $dateStr = $immutable->toDateString();

            if ($existingByDate->has($dateStr)) {
                $merged->push($existingByDate->get($dateStr));
            } elseif ($this->clock->isWeekend($immutable)) {
                // Synthetic day off
                $dayOff = new Attendance([
                    'employee_id' => $employee->id,
                    'attendance_date' => $dateStr,
                    'status' => AttendanceStatus::DayOff,
                    'review_status' => ReviewStatus::NotApplicable,
                    'late_minutes' => 0,
                    'early_leave_minutes' => 0,
                ]);
                $dayOff->setRelation('employee', $employee);
                $dayOff->setRelation('shift', null);
                $dayOff->setRelation('reviewer', null);

                $matchStatus = empty($filters['status']) || $filters['status'] === AttendanceStatus::DayOff->value;
                $matchReview = empty($filters['review_status']) || $filters['review_status'] === ReviewStatus::NotApplicable->value;
                $matchView = empty($filters['view']) || $filters['view'] === 'absences';

                if ($matchStatus && $matchReview && $matchView) {
                    $merged->push($dayOff);
                }
            }
        }

        return $merged->sortByDesc(fn ($r) => is_string($r->attendance_date) ? $r->attendance_date : $r->attendance_date->toDateString())->values();
    }
}
