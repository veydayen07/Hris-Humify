<?php

namespace App\Models;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\ShiftCategory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    /** @use HasFactory<\Database\Factories\EmployeeFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'employee_code',
        'full_name',
        'email',
        'personal_email',
        'phone',
        'gender',
        'birth_place',
        'birth_date',
        'address',
        'photo_url',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
        'face_reference_photo_url',
        'face_registered_at',
        'face_registered_by',
        'department_id',
        'position_id',
        'supervisor_id',
        'employment_type',
        'employee_status',
        'is_shift_worker',
        'hire_date',
        'resign_date',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'hire_date' => 'date',
            'resign_date' => 'date',
            'face_registered_at' => 'datetime',
            'employment_type' => EmploymentType::class,
            'employee_status' => EmployeeStatus::class,
            'is_shift_worker' => 'boolean',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'supervisor_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(Employee::class, 'supervisor_id');
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function faceRegisteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'face_registered_by');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function isFaceRegistered(): bool
    {
        return $this->face_reference_photo_url !== null && $this->face_registered_at !== null;
    }

    public function workCategory(): ShiftCategory
    {
        return $this->is_shift_worker ? ShiftCategory::Shifting : ShiftCategory::Regular;
    }

    public function workCategoryValue(): string
    {
        return $this->workCategory()->value;
    }

    public function isActive(): bool
    {
        return $this->employee_status === EmployeeStatus::Active;
    }
}
