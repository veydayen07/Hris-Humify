<?php

namespace App\Http\Resources;

use App\Models\LeaveBalance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LeaveBalance
 */
class LeaveBalanceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'leave_type_id' => $this->leave_type_id,
            'leave_type_name' => $this->leaveType?->name,
            'year' => (int) $this->year,
            'total_days' => (float) $this->total_days,
            'used_days' => (float) $this->used_days,
            'remaining_days' => $this->remainingDays(),
        ];
    }
}

