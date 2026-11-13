<?php

namespace App\Services;

use App\Models\User;

class JwtService
{
    /**
     * Generate standard JWT token.
     */
    public static function generateToken(User $user, int $ttl = 86400): string
    {
        $header = self::base64UrlEncode(json_encode([
            'typ' => 'JWT',
            'alg' => 'HS256',
        ]));

        $payload = self::base64UrlEncode(json_encode([
            'sub' => (string) $user->id,
            'username' => $user->username,
            'role' => is_object($user->role) ? $user->role->value : (string) $user->role,
            'iat' => time(),
            'exp' => time() + $ttl,
        ]));

        $secret = config('app.key') ?: 'humify_secret_key_hris_fallback_2026';
        $signature = self::base64UrlEncode(hash_hmac('sha256', "{$header}.{$payload}", $secret, true));

        return "{$header}.{$payload}.{$signature}";
    }

    /**
     * Validate JWT token and return user ID, or null if invalid.
     */
    public static function validateToken(string $jwt): ?string
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return null;
        }

        [$headerB64, $payloadB64, $sigB64] = $parts;

        $secret = config('app.key') ?: 'humify_secret_key_hris_fallback_2026';
        $expectedSig = self::base64UrlEncode(hash_hmac('sha256', "{$headerB64}.{$payloadB64}", $secret, true));

        if (! hash_equals($expectedSig, $sigB64)) {
            return null;
        }

        $payloadJson = self::base64UrlDecode($payloadB64);
        if (! $payloadJson) {
            return null;
        }

        $payload = json_decode($payloadJson, true);
        if (! is_array($payload) || ! isset($payload['sub'], $payload['exp'])) {
            return null;
        }

        if (time() > $payload['exp']) {
            return null;
        }

        return (string) $payload['sub'];
    }

    public static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function base64UrlDecode(string $data): string|false
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($data, '-_', '+/'));
    }
}

