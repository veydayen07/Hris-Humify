<?php

namespace App\Models;

use App\Enums\LeaveAttendanceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    /** @use HasFactory<\Database\Factories\LeaveTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'is_paid',
        'max_days_per_year',
        'requires_attachment',
        'tracks_balance',
        'attendance_status',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_paid' => 'boolean',
            'requires_attachment' => 'boolean',
            'tracks_balance' => 'boolean',
            'is_active' => 'boolean',
            'attendance_status' => LeaveAttendanceStatus::class,
            'max_days_per_year' => 'integer',
        ];
    }

    public function balances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }
}
