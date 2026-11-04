<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use App\Enums\ReviewStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    /** @use HasFactory<\Database\Factories\AttendanceFactory> */
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'shift_id',
        'attendance_date',
        'check_in_time',
        'check_in_lat',
        'check_in_lng',
        'check_in_photo_url',
        'check_in_face_verified',
        'check_in_face_score',
        'check_in_work_location_id',
        'check_in_geofence_valid',
        'check_in_distance_m',
        'check_out_time',
        'check_out_lat',
        'check_out_lng',
        'check_out_photo_url',
        'check_out_face_verified',
        'check_out_face_score',
        'check_out_work_location_id',
        'check_out_geofence_valid',
        'check_out_distance_m',
        'status',
        'late_minutes',
        'early_leave_minutes',
        'work_minutes',
        'notes',
        'review_status',
        'reviewed_by',
        'reviewed_at',
        'review_note',
        'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'check_in_time' => 'datetime',
            'check_out_time' => 'datetime',
            'check_in_face_verified' => 'boolean',
            'check_out_face_verified' => 'boolean',
            'check_in_geofence_valid' => 'boolean',
            'check_out_geofence_valid' => 'boolean',
            'check_in_face_score' => 'float',
            'check_out_face_score' => 'float',
            'status' => AttendanceStatus::class,
            'review_status' => ReviewStatus::class,
            'reviewed_at' => 'datetime',
            'late_minutes' => 'integer',
            'early_leave_minutes' => 'integer',
            'work_minutes' => 'integer',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(WorkShift::class, 'shift_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function checkInWorkLocation(): BelongsTo
    {
        return $this->belongsTo(WorkLocation::class, 'check_in_work_location_id');
    }

    public function checkOutWorkLocation(): BelongsTo
    {
        return $this->belongsTo(WorkLocation::class, 'check_out_work_location_id');
    }

    public function isPendingReview(): bool
    {
        return $this->review_status === ReviewStatus::Pending;
    }

    public function isResolved(): bool
    {
        return in_array($this->review_status, [ReviewStatus::Approved, ReviewStatus::Rejected], true);
    }
}
