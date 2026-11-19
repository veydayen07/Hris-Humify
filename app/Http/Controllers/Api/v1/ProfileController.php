<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\EmployeeDataFormatter;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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

    /**
     * 2.2 Pendaftaran Foto Wajah Acuan Karyawan
     */
    public function registerFace(Request $request): JsonResponse
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

        $file = $request->file('face_photo') ?: $request->file('photo');
        $base64 = $request->input('face_photo_base64');

        if (! $file && ! $base64) {
            $inputPhoto = $request->input('face_photo') ?: $request->input('photo');
            if (is_string($inputPhoto) && (str_starts_with($inputPhoto, 'data:image') || strlen($inputPhoto) > 50)) {
                $base64 = $inputPhoto;
            }
        }

        if (! $file && ! $base64) {
            return response()->json([
                'message' => 'Berkas atau data foto wajah tidak ditemukan dalam request.',
                'code' => 'FILE_MISSING',
                'errors' => null,
            ], 422);
        }

        $now = Carbon::now('Asia/Jakarta');
        $timestamp = time();

        if ($file) {
            $validator = Validator::make(['photo' => $file], [
                'photo' => ['file', 'image', 'max:10240'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validasi foto gagal. Pastikan berkas berupa gambar (JPG/PNG) maksimal 10MB.',
                    'code' => 'VALIDATION_ERROR',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $ext = $file->getClientOriginalExtension() ?: 'jpg';
            $filename = 'face_ref_' . $employee->id . '_' . $timestamp . '.' . $ext;
            $path = $file->storeAs('faces', $filename, 'public');
            $photoUrl = url(Storage::url($path));
        } else {
            // Proses base64 image data
            $ext = 'jpg';
            if (preg_match('/^data:image\/(\w+);base64,/', $base64, $type)) {
                $base64 = substr($base64, strpos($base64, ',') + 1);
                $ext = strtolower($type[1]);
                if ($ext === 'jpeg') {
                    $ext = 'jpg';
                }
            }

            $binaryData = base64_decode($base64);
            if ($binaryData === false || strlen($binaryData) < 20) {
                return response()->json([
                    'message' => 'Format base64 data foto wajah tidak valid.',
                    'code' => 'INVALID_BASE64',
                    'errors' => null,
                ], 422);
            }

            $filename = 'face_ref_' . $employee->id . '_' . $timestamp . '.' . $ext;
            Storage::disk('public')->put('faces/' . $filename, $binaryData);
            $photoUrl = url(Storage::url('faces/' . $filename));
        }

        // Update database karyawan
        $employee->update([
            'face_reference_photo_url' => $photoUrl,
            'photo_url' => $photoUrl,
            'face_registered_at' => $now,
        ]);
        $employee->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Foto wajah acuan berhasil didaftarkan.',
            'data' => [
                'face_reference_photo_url' => $photoUrl,
                'photo_url' => $photoUrl,
                'face_registered' => true,
                'face_registered_at' => $now->format('Y-m-d H:i:s'),
                'employee' => EmployeeDataFormatter::formatEmployee($employee),
            ],
        ]);
    }
}

