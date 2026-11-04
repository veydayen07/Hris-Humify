<?php

namespace App\Http\Resources;

use App\Models\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LeaveType
 */
class LeaveTypeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_paid' => (bool) $this->is_paid,
            'max_days_per_year' => $this->max_days_per_year,
            'requires_attachment' => (bool) $this->requires_attachment,
            'tracks_balance' => (bool) $this->tracks_balance,
            'attendance_status' => $this->attendance_status->value,
            'is_active' => (bool) $this->is_active,
        ];
    }
}

