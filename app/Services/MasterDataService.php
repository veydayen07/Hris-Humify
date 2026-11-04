<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Position;
use App\Models\User;
use App\Models\WorkLocation;
use App\Models\WorkShift;
use Illuminate\Database\Eloquent\Collection;

class MasterDataService
{
    public function __construct(
        private AuditLogger $auditLogger,
    ) {}

    /**
     * @return Collection<int, Department>
     */
    public function getDepartments(): Collection
    {
        return Department::query()->orderBy('name')->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createDepartment(User $actor, array $data): Department
    {
        $dept = Department::query()->create($data);
        $this->auditLogger->log($actor, 'department.created', $dept, $data);

        return $dept;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateDepartment(User $actor, Department $department, array $data): Department
    {
        $department->update($data);
        $this->auditLogger->log($actor, 'department.updated', $department, $data);

        return $department->fresh();
    }

    /**
     * @return Collection<int, Position>
     */
    public function getPositions(?int $departmentId = null): Collection
    {
        $query = Position::query()->with('department');

        if ($departmentId !== null) {
            $query->where('department_id', $departmentId);
        }

        return $query->orderBy('name')->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createPosition(User $actor, array $data): Position
    {
        $pos = Position::query()->create($data);
        $this->auditLogger->log($actor, 'position.created', $pos, $data);

        return $pos->fresh('department');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updatePosition(User $actor, Position $position, array $data): Position
    {
        $position->update($data);
        $this->auditLogger->log($actor, 'position.updated', $position, $data);

        return $position->fresh('department');
    }

    /**
     * @return Collection<int, WorkShift>
     */
    public function getAllShifts(): Collection
    {
        return WorkShift::query()->orderBy('id')->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createShift(User $actor, array $data): WorkShift
    {
        $shift = WorkShift::query()->create($data);
        $this->auditLogger->log($actor, 'work_shift.created', $shift, $data);

        return $shift;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateShift(User $actor, WorkShift $shift, array $data): WorkShift
    {
        $shift->update($data);
        $this->auditLogger->log($actor, 'work_shift.updated', $shift, $data);

        return $shift->fresh();
    }

    /**
     * @return Collection<int, WorkLocation>
     */
    public function getWorkLocations(): Collection
    {
        return WorkLocation::query()->orderBy('name')->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createWorkLocation(User $actor, array $data): WorkLocation
    {
        $loc = WorkLocation::query()->create($data);
        $this->auditLogger->log($actor, 'work_location.created', $loc, $data);

        return $loc;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateWorkLocation(User $actor, WorkLocation $location, array $data): WorkLocation
    {
        $location->update($data);
        $this->auditLogger->log($actor, 'work_location.updated', $location, $data);

        return $location->fresh();
    }
}

