<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?string $role = null): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json([
                'message' => 'Token autentikasi tidak disertakan.',
                'code' => 'UNAUTHENTICATED',
                'errors' => null,
            ], 401);
        }

        $userId = JwtService::validateToken($token);

        if (! $userId) {
            return response()->json([
                'message' => 'Token tidak valid atau sesi telah kedaluwarsa.',
                'code' => 'UNAUTHENTICATED',
                'errors' => null,
            ], 401);
        }

        $user = User::with(['employee.department', 'employee.position', 'employee.supervisor'])->find($userId);

        if (! $user || ! $user->is_active) {
            return response()->json([
                'message' => 'Pengguna tidak ditemukan atau akun dinonaktifkan.',
                'code' => 'UNAUTHENTICATED',
                'errors' => null,
            ], 401);
        }

        if ($role) {
            $userRole = is_object($user->role) ? $user->role->value : (string) $user->role;
            $allowedRoles = array_map('trim', explode('|', $role));

            if (! in_array($userRole, $allowedRoles, true)) {
                // Check if role is manager or admin_hr
                $isAllowed = false;
                foreach ($allowedRoles as $r) {
                    if ($r === 'manager' && ($userRole === 'manager' || $userRole === 'admin_hr' || $userRole === 'super_admin')) {
                        $isAllowed = true;
                        break;
                    }
                    if ($r === 'admin_hr' && ($userRole === 'admin_hr' || $userRole === 'super_admin')) {
                        $isAllowed = true;
                        break;
                    }
                }

                if (! $isAllowed) {
                    return response()->json([
                        'message' => 'Pengguna tidak memiliki wewenang untuk aksi ini.',
                        'code' => 'FORBIDDEN',
                        'errors' => null,
                    ], 403);
                }
            }
        }

        Auth::setUser($user);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}

