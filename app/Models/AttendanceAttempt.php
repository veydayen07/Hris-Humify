<?php

namespace App\Models;

use App\Enums\AttendanceAttemptOutcome;
use App\Enums\AttendanceAttemptType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceAttempt extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'employee_id',
        'type',
        'outcome',
        'failure_code',
        'face_score',
        'distance_m',
        'work_location_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => AttendanceAttemptType::class,
            'outcome' => AttendanceAttemptOutcome::class,
            'face_score' => 'float',
            'distance_m' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function workLocation(): BelongsTo
    {
        return $this->belongsTo(WorkLocation::class);
    }
}
