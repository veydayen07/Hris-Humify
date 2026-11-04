<?php

namespace App\Http\Resources;

use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LeaveRequest
 */
class LeaveRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'employee_code' => $this->employee?->employee_code,
            'employee_name' => $this->employee?->full_name,
            'department_name' => $this->employee?->department?->name,
            'leave_type' => [
                'id' => $this->leaveType?->id,
                'name' => $this->leaveType?->name,
                'requires_attachment' => (bool) $this->leaveType?->requires_attachment,
                'tracks_balance' => (bool) $this->leaveType?->tracks_balance,
            ],
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'total_days' => (float) $this->total_days,
            'reason' => $this->reason,
            'has_attachment' => ! empty($this->attachment_url),
            'status' => $this->status->value,
            'approved_by' => $this->approver ? [
                'id' => $this->approver->id,
                'name' => $this->approver->employee?->full_name ?? $this->approver->username,
            ] : null,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'rejection_reason' => $this->rejection_reason,
            'canceled_by' => $this->canceled_by,
            'canceled_at' => $this->canceled_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

