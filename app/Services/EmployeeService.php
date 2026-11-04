<?php

namespace App\Services;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\ShiftCategory;
use App\Exceptions\ApiException;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class EmployeeService
{
    public function __construct(
        private AuditLogger $auditLogger,
        private ProfileService $profileService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters): LengthAwarePaginator
    {
        $perPage = min(max((int) ($filters['per_page'] ?? 15), 1), 100);

        $query = Employee::query()->with(['department', 'position', 'supervisor']);

        if (! empty($filters['q'])) {
            $q = (string) $filters['q'];
            $query->where(function (Builder $b) use ($q) {
                $b->where('full_name', 'like', "%{$q}%")
                    ->orWhere('employee_code', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        if (! empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('employee_status', $filters['status']);
        }

        if (! empty($filters['work_category'])) {
            $isShift = $filters['work_category'] === 'shifting';
            $query->where('is_shift_worker', $isShift);
        }

        return $query->orderBy('full_name')->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $actor, array $data): Employee
    {
        if (isset($data['work_category'])) {
            $data['is_shift_worker'] = $data['work_category'] === 'shifting';
            unset($data['work_category']);
        }

        $data['employee_status'] = EmployeeStatus::Active;

        $employee = Employee::query()->create($data);

        $this->auditLogger->log($actor, 'employee.created', $employee, $data);

        return $employee->fresh(['department', 'position', 'supervisor']);
    }

    public function show(string $id): Employee
    {
        return Employee::query()
            ->with(['department', 'position', 'supervisor', 'user', 'faceRegisteredBy'])
            ->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $actor, Employee $employee, array $data): Employee
    {
        if (isset($data['work_category'])) {
            $data['is_shift_worker'] = $data['work_category'] === 'shifting';
            unset($data['work_category']);
        }

        $employee->update($data);

        $this->auditLogger->log($actor, 'employee.updated', $employee, $data);

        return $employee->fresh(['department', 'position', 'supervisor', 'user']);
    }

    public function deactivate(User $actor, Employee $employee, bool $confirm): Employee
    {
        if (! $confirm) {
            throw ApiException::validation('Confirmation is required to deactivate an employee.', [
                'confirm' => ['The confirm field must be true.'],
            ]);
        }

        DB::transaction(function () use ($actor, $employee) {
            $employee->update([
                'employee_status' => EmployeeStatus::Inactive,
            ]);

            if ($employee->user) {
                $employee->user->update([
                    'is_active' => false,
                ]);

                $employee->user->tokens()->delete();
            }

            $this->auditLogger->log($actor, 'employee.deactivated', $employee);
        });

        return $employee->fresh(['user']);
    }

    /**
     * @return array<string, mixed>
     */
    public function getFaceReference(Employee $employee): array
    {
        $signedUrl = null;

        if ($employee->face_reference_photo_url && Storage::disk('local')->exists($employee->face_reference_photo_url)) {
            $signedUrl = URL::temporarySignedRoute(
                'api.v1.employees.face-photo',
                now()->addMinutes(config('humify.signed_url_ttl_minutes', 5)),
                ['id' => $employee->id],
            );
        }

        return [
            'face_registered' => $employee->isFaceRegistered(),
            'face_registered_at' => $employee->face_registered_at?->toIso8601String(),
            'face_registered_by' => $employee->faceRegisteredBy ? [
                'id' => $employee->faceRegisteredBy->id,
                'username' => $employee->faceRegisteredBy->username,
            ] : null,
            'photo_url' => $signedUrl,
        ];
    }

    public function updateFaceReference(User $actor, Employee $employee, UploadedFile $photo, bool $confirm): Employee
    {
        if (! $confirm) {
            throw ApiException::validation('Confirmation is required to update face reference photo.', [
                'confirm' => ['The confirm field must be true.'],
            ]);
        }

        return $this->profileService->replaceFaceReference($actor, $employee, $photo);
    }
}

