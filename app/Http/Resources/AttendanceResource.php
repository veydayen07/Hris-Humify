<?php

namespace App\Http\Resources;

use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Attendance
 */
class AttendanceResource extends JsonResource
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
            'attendance_date' => is_string($this->attendance_date) ? $this->attendance_date : $this->attendance_date?->toDateString(),
            'shift' => $this->shift ? [
                'id' => $this->shift->id,
                'name' => $this->shift->name,
                'start_time' => $this->shift->start_time,
                'end_time' => $this->shift->end_time,
                'category' => $this->shift->category->value,
            ] : null,
            'check_in' => [
                'time' => $this->check_in_time?->toIso8601String(),
                'face_verified' => $this->check_in_face_verified,
                'face_score' => $this->check_in_face_score,
                'geofence_valid' => $this->check_in_geofence_valid,
                'distance_m' => $this->check_in_distance_m,
                'work_location' => $this->checkInWorkLocation?->name,
            ],
            'check_out' => [
                'time' => $this->check_out_time?->toIso8601String(),
                'face_verified' => $this->check_out_face_verified,
                'face_score' => $this->check_out_face_score,
                'geofence_valid' => $this->check_out_geofence_valid,
                'distance_m' => $this->check_out_distance_m,
                'work_location' => $this->checkOutWorkLocation?->name,
            ],
            'status' => $this->status?->value ?? (string) $this->status,
            'late_minutes' => (int) $this->late_minutes,
            'early_leave_minutes' => (int) $this->early_leave_minutes,
            'work_minutes' => $this->work_minutes !== null ? (int) $this->work_minutes : null,
            'notes' => $this->notes,
            'review_status' => $this->review_status?->value ?? (string) $this->review_status,
            'reviewer' => $this->reviewer ? [
                'id' => $this->reviewer->id,
                'name' => $this->reviewer->employee?->full_name ?? $this->reviewer->username,
            ] : null,
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'review_note' => $this->review_note,
        ];
    }
}

