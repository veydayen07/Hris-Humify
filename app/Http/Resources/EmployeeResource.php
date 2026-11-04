<?php

namespace App\Http\Resources;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Employee
 */
class EmployeeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_code' => $this->employee_code,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'personal_email' => $this->personal_email,
            'phone' => $this->phone,
            'gender' => $this->gender,
            'birth_place' => $this->birth_place,
            'birth_date' => $this->birth_date?->toDateString(),
            'address' => $this->address,
            'photo_url' => $this->photo_url,
            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_phone' => $this->emergency_contact_phone,
            'emergency_contact_relationship' => $this->emergency_contact_relationship,
            'department_id' => $this->department_id,
            'department' => $this->department?->name,
            'position_id' => $this->position_id,
            'position' => $this->position?->name,
            'supervisor_id' => $this->supervisor_id,
            'supervisor' => $this->supervisor ? [
                'id' => $this->supervisor->id,
                'employee_code' => $this->supervisor->employee_code,
                'full_name' => $this->supervisor->full_name,
            ] : null,
            'employment_type' => $this->employment_type?->value,
            'employee_status' => $this->employee_status?->value,
            'is_shift_worker' => (bool) $this->is_shift_worker,
            'work_category' => $this->workCategoryValue(),
            'hire_date' => $this->hire_date?->toDateString(),
            'resign_date' => $this->resign_date?->toDateString(),
            'face_registered' => $this->isFaceRegistered(),
            'face_registered_at' => $this->face_registered_at?->toIso8601String(),
            'face_registered_by' => $this->face_registered_by,
        ];
    }
}

