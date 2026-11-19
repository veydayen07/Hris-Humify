<?php

namespace App\Support;

use App\Models\Employee;
use App\Models\User;

class EmployeeDataFormatter
{
    /**
     * Format employee object for API responses.
     */
    public static function formatEmployee(?Employee $employee): ?array
    {
        if (! $employee) {
            return null;
        }

        $faceRegistered = ! empty($employee->face_reference_photo_url) || ! empty($employee->face_registered_at);

        return [
            'id' => (string) $employee->id,
            'employee_code' => (string) $employee->employee_code,
            'full_name' => (string) $employee->full_name,
            'email' => (string) ($employee->email ?? ''),
            'phone' => (string) ($employee->phone ?? ''),
            'gender' => is_object($employee->gender) ? $employee->gender->value : (string) ($employee->gender ?? 'L'),
            'birth_place' => (string) ($employee->birth_place ?? ''),
            'birth_date' => $employee->birth_date ? $employee->birth_date->format('Y-m-d') : null,
            'address' => (string) ($employee->address ?? ''),
            'photo_url' => $employee->photo_url ?: $employee->face_reference_photo_url,
            'face_registered' => $faceRegistered,
            'department' => $employee->department ? [
                'id' => (int) $employee->department->id,
                'name' => (string) $employee->department->name,
            ] : null,
            'position' => $employee->position ? [
                'id' => (int) $employee->position->id,
                'name' => (string) $employee->position->name,
            ] : null,
            'supervisor' => $employee->supervisor ? [
                'id' => (string) $employee->supervisor->id,
                'full_name' => (string) $employee->supervisor->full_name,
            ] : null,
            'employment_type' => is_object($employee->employment_type) ? $employee->employment_type->value : (string) ($employee->employment_type ?? 'tetap'),
            'employee_status' => is_object($employee->employee_status) ? $employee->employee_status->value : (string) ($employee->employee_status ?? 'aktif'),
            'is_shift_worker' => (bool) $employee->is_shift_worker,
        ];
    }

    /**
     * Format user with employee.
     */
    public static function formatUser(User $user): array
    {
        return [
            'id' => (string) $user->id,
            'username' => (string) $user->username,
            'role' => is_object($user->role) ? $user->role->value : (string) $user->role,
            'employee' => self::formatEmployee($user->employee),
        ];
    }
}

