<?php

namespace App\Services\Face;

use App\Contracts\FaceVerifier;
use RuntimeException;

class FakeFaceVerifier implements FaceVerifier
{
    public function verify(string $referencePhotoPath, string $attemptPhotoPath): FaceVerificationResult
    {
        if (config('humify.face_verifier.fake.throw', false)) {
            throw new RuntimeException('Fake face verifier forced failure.');
        }

        return new FaceVerificationResult(
            matched: (bool) config('humify.face_verifier.fake.matched', true),
            score: (float) config('humify.face_verifier.fake.score', 95),
        );
    }
}
