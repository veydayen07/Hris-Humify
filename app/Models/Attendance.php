<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use App\Enums\ReviewStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $employee_id
 * @property int|null $shift_id
 * @property \Carbon\Carbon|string|null $attendance_date
 * @property \Carbon\Carbon|string|null $check_in_time
 * @property float|null $check_in_lat
 * @property float|null $check_in_lng
 * @property string|null $check_in_photo_url
 * @property bool $check_in_face_verified
 * @property float|null $check_in_face_score
 * @property \Carbon\Carbon|string|null $check_out_time
 * @property float|null $check_out_lat
 * @property float|null $check_out_lng
 * @property string|null $check_out_photo_url
 * @property bool $check_out_face_verified
 * @property float|null $check_out_face_score
 * @property AttendanceStatus|string $status
 * @property int $late_minutes
 * @property int $early_leave_minutes
 * @property int $work_minutes
 * @property string|null $notes
 * @property ReviewStatus|string $review_status
 * @property int|null $reviewed_by
 * @property \Carbon\Carbon|string|null $reviewed_at
 * @property string|null $review_note
 * @property-read \App\Models\WorkShift|null $workShift
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\User|null $reviewer
 * @method bool save(array $options = [])
 */
class Attendance extends Model
{
    /**
     * @var list<string>
     */
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
        'check_out_time',
        'check_out_lat',
        'check_out_lng',
        'check_out_photo_url',
        'check_out_face_verified',
        'check_out_face_score',
        'status',
        'late_minutes',
        'early_leave_minutes',
        'work_minutes',
        'notes',
        'review_status',
        'reviewed_by',
        'reviewed_at',
        'review_note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'check_in_time' => 'datetime',
            'check_out_time' => 'datetime',
            'reviewed_at' => 'datetime',
            'check_in_lat' => 'float',
            'check_in_lng' => 'float',
            'check_out_lat' => 'float',
            'check_out_lng' => 'float',
            'check_in_face_verified' => 'boolean',
            'check_out_face_verified' => 'boolean',
            'check_in_face_score' => 'decimal:2',
            'check_out_face_score' => 'decimal:2',
            'status' => AttendanceStatus::class,
            'review_status' => ReviewStatus::class,
            'late_minutes' => 'integer',
            'early_leave_minutes' => 'integer',
            'work_minutes' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * @return BelongsTo<WorkShift, $this>
     */
    public function workShift(): BelongsTo
    {
        return $this->belongsTo(WorkShift::class, 'shift_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
