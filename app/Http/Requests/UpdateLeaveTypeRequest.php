<?php

namespace App\Http\Requests;

use App\Enums\LeaveAttendanceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeaveTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'name' => ['sometimes', 'string', 'max:50', Rule::unique('leave_types', 'name')->ignore($id)],
            'is_paid' => ['sometimes', 'boolean'],
            'max_days_per_year' => ['nullable', 'integer', 'min:0'],
            'requires_attachment' => ['sometimes', 'boolean'],
            'tracks_balance' => ['sometimes', 'boolean'],
            'attendance_status' => ['sometimes', Rule::enum(LeaveAttendanceStatus::class)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}

