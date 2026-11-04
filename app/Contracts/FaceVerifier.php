<?php

namespace App\Contracts;

use App\Services\Face\FaceVerificationResult;

interface FaceVerifier
{
    public function verify(string $referencePhotoPath, string $attemptPhotoPath): FaceVerificationResult;
}
