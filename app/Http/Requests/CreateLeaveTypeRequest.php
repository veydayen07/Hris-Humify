<?php

namespace App\Http\Requests;

use App\Enums\LeaveAttendanceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateLeaveTypeRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:50', 'unique:leave_types,name'],
            'is_paid' => ['sometimes', 'boolean'],
            'max_days_per_year' => ['nullable', 'integer', 'min:0'],
            'requires_attachment' => ['sometimes', 'boolean'],
            'tracks_balance' => ['sometimes', 'boolean'],
            'attendance_status' => ['required', Rule::enum(LeaveAttendanceStatus::class)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}

