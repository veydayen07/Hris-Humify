<?php

namespace App\Services\Face;

use App\Contracts\FaceVerifier;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class HttpFaceVerifier implements FaceVerifier
{
    public function verify(string $referencePhotoPath, string $attemptPhotoPath): FaceVerificationResult
    {
        $baseUrl = config('humify.face_verifier.http.base_url');

        if (blank($baseUrl)) {
            throw new RuntimeException('Face verifier HTTP base URL is not configured.');
        }

        $response = Http::baseUrl((string) $baseUrl)
            ->timeout((int) config('humify.face_verifier.http.timeout', 10))
            ->withToken((string) config('humify.face_verifier.http.api_key'))
            ->attach('reference', file_get_contents($referencePhotoPath), basename($referencePhotoPath))
            ->attach('attempt', file_get_contents($attemptPhotoPath), basename($attemptPhotoPath))
            ->post('/verify');

        if ($response->failed()) {
            throw new RuntimeException('Face verification provider request failed.');
        }

        /** @var array{matched?: bool, score?: float|int|string} $payload */
        $payload = $response->json();

        return new FaceVerificationResult(
            matched: (bool) ($payload['matched'] ?? false),
            score: (float) ($payload['score'] ?? 0),
        );
    }
}
