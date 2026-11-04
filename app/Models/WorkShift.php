<?php

namespace App\Models;

use App\Enums\ShiftCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkShift extends Model
{
    /** @use HasFactory<\Database\Factories\WorkShiftFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'category',
        'is_overnight',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'category' => ShiftCategory::class,
            'is_overnight' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'shift_id');
    }

    public function isConfigured(): bool
    {
        return $this->start_time !== null && $this->end_time !== null;
    }
}
