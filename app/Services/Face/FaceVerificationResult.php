<?php

namespace App\Services\Face;

class FaceVerificationResult
{
    public function __construct(
        public readonly bool $matched,
        public readonly float $score,
    ) {}
}
