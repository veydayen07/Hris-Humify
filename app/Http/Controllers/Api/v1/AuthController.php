<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\JwtService;
use App\Support\EmployeeDataFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * 1.1 Health Check / Ping
     */
    public function ping(): JsonResponse
    {
        return response()->json([
            'message' => 'Humify API v1 is ready.',
        ]);
    }

    /**
     * 1.2 Login Pengguna
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'code' => 'VALIDATION_ERROR',
                'errors' => $validator->errors(),
            ], 422);
        }

        $username = trim($request->input('username'));
        $password = (string) $request->input('password');

        $user = User::with(['employee.department', 'employee.position', 'employee.supervisor'])
            ->where('username', $username)
            ->first();

        $hash = $user?->password_hash ?: $user?->password;

        if (! $user || ! $hash || ! Hash::check($password, $hash)) {
            return response()->json([
                'message' => 'Username atau password salah.',
                'code' => 'INVALID_CREDENTIALS',
                'errors' => null,
            ], 401);
        }

        if (! $user->is_active) {
            return response()->json([
                'message' => 'Akun dinonaktifkan. Silakan hubungi admin HR.',
                'code' => 'ACCOUNT_INACTIVE',
                'errors' => null,
            ], 401);
        }

        $user->update(['last_login_at' => now()]);

        $token = JwtService::generateToken($user);

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => 86400,
                'user' => EmployeeDataFormatter::formatUser($user),
            ],
        ]);
    }

    /**
     * 1.3 Logout
     */
    public function logout(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Berhasil logout.',
        ]);
    }

    /**
     * 1.4 Profil Pengguna Saat Ini
     */
    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->loadMissing(['employee.department', 'employee.position', 'employee.supervisor']);

        return response()->json([
            'success' => true,
            'data' => EmployeeDataFormatter::formatUser($user),
        ]);
    }
}

