<?php

namespace App\Services;

use App\Enums\EmployeeStatus;
use App\Exceptions\ApiException;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthService
{
    /**
     * @return array{token: string, user: User}
     */
    public function login(string $username, string $password, string $deviceName, Request $request): array
    {
        $throttleKey = $this->throttleKey($username, $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ApiException::tooManyAttempts(RateLimiter::availableIn($throttleKey));
        }

        $user = User::query()
            ->with(['employee.department', 'employee.position'])
            ->where('username', $username)
            ->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            RateLimiter::hit($throttleKey, 60);

            throw ApiException::invalidCredentials();
        }

        if (! $user->is_active || $user->employee === null || $user->employee->employee_status !== EmployeeStatus::Active) {
            RateLimiter::hit($throttleKey, 60);

            throw ApiException::invalidCredentials();
        }

        RateLimiter::clear($throttleKey);

        $user->forceFill(['last_login_at' => now()])->save();

        $expiration = config('humify.token_expiration_minutes');
        $token = $user->createToken(
            $deviceName,
            ['*'],
            $expiration ? now()->addMinutes((int) $expiration) : null,
        )->plainTextToken;

        return [
            'token' => $token,
            'user' => $user->fresh(['employee.department', 'employee.position']),
        ];
    }

    public function logout(User $user, Request $request): void
    {
        $request->user()?->currentAccessToken()?->delete();
    }

    private function throttleKey(string $username, ?string $ip): string
    {
        return Str::transliterate(Str::lower($username).'|'.($ip ?? 'unknown'));
    }
}
