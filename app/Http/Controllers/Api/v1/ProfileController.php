<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    /**
     * 2.1 Perbarui Data Personal Karyawan
     */
    public function update(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $employee = $user->employee;

        if (! $employee) {
            return response()->json([
                'message' => 'Data karyawan tidak ditemukan.',
                'code' => 'NOT_FOUND',
                'errors' => null,
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:500'],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date_format:Y-m-d'],
            'gender' => ['nullable', 'in:L,P'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'code' => 'VALIDATION_ERROR',
                'errors' => $validator->errors(),
            ], 422);
        }

        $fields = ['phone', 'email', 'address', 'birth_place', 'birth_date', 'gender'];
        $dataToUpdate = [];

        foreach ($fields as $field) {
            if ($request->has($field)) {
                $dataToUpdate[$field] = $request->input($field);
            }
        }

        if (! empty($dataToUpdate)) {
            $employee->update($dataToUpdate);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data personal berhasil diperbarui.',
            'data' => [
                'phone' => (string) ($employee->phone ?? ''),
                'email' => (string) ($employee->email ?? ''),
                'address' => (string) ($employee->address ?? ''),
                'birth_place' => (string) ($employee->birth_place ?? ''),
                'birth_date' => $employee->birth_date ? $employee->birth_date->format('Y-m-d') : null,
                'gender' => is_object($employee->gender) ? $employee->gender->value : (string) ($employee->gender ?? 'L'),
            ],
        ]);
    }
}

