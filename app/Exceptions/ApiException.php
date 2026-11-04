<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class ApiException extends Exception
{
    /**
     * @param  array<string, mixed>  $errors
     * @param  array<string, mixed>|null  $data
     * @param  array<string, mixed>|null  $meta
     */
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $status = 422,
        public readonly array $errors = [],
        public readonly ?array $data = null,
        public readonly ?array $meta = null,
    ) {
        parent::__construct($message, $status);
    }

    public function render(): JsonResponse
    {
        $payload = [
            'message' => $this->getMessage(),
            'code' => $this->errorCode,
            'errors' => (object) $this->errors,
        ];

        if ($this->data !== null) {
            $payload['data'] = $this->data;
        }

        if ($this->meta !== null) {
            $payload['meta'] = $this->meta;
        }

        return response()->json($payload, $this->status);
    }

    public static function invalidCredentials(): self
    {
        return new self('INVALID_CREDENTIALS', 'Invalid username or password.', 401);
    }

    public static function tooManyAttempts(int $retryAfter): self
    {
        return new self(
            'TOO_MANY_ATTEMPTS',
            'Too many login attempts. Please try again later.',
            429,
            meta: ['retry_after' => $retryAfter],
        );
    }

    public static function forbidden(string $message = 'You do not have permission to perform this action.'): self
    {
        return new self('FORBIDDEN', $message, 403);
    }

    public static function validation(string $message = 'The given data was invalid.', array $errors = []): self
    {
        return new self('VALIDATION_ERROR', $message, 422, $errors);
    }

    public static function faceNotRegistered(): self
    {
        return new self('FACE_NOT_REGISTERED', 'Face reference photo is not registered.', 422);
    }

    public static function faceAlreadyRegistered(): self
    {
        return new self('FACE_ALREADY_REGISTERED', 'Face reference photo is already registered.', 409);
    }

    public static function faceMismatch(?float $score = null): self
    {
        return new self(
            'FACE_MISMATCH',
            'Face verification did not match.',
            422,
            data: $score === null ? null : ['face_score' => $score],
        );
    }

    public static function faceServiceUnavailable(): self
    {
        return new self('FACE_SERVICE_UNAVAILABLE', 'Face verification service is unavailable.', 503);
    }

    public static function outsidePermittedArea(?int $distanceM = null): self
    {
        return new self(
            'OUTSIDE_PERMITTED_AREA',
            'Location is outside the permitted work area.',
            422,
            data: $distanceM === null ? null : ['distance_m' => $distanceM],
        );
    }

    public static function shiftNotAllowed(): self
    {
        return new self('SHIFT_NOT_ALLOWED', 'Selected shift is not allowed for this employee.', 422);
    }

    public static function shiftNotConfigured(): self
    {
        return new self('SHIFT_NOT_CONFIGURED', 'Selected shift times are not configured.', 422);
    }

    public static function alreadyCheckedIn(array $data): self
    {
        return new self('ALREADY_CHECKED_IN', 'Already checked in for this attendance date.', 409, data: $data);
    }

    public static function notCheckedIn(): self
    {
        return new self('NOT_CHECKED_IN', 'No open check-in found for check-out.', 409);
    }

    public static function alreadyCheckedOut(): self
    {
        return new self('ALREADY_CHECKED_OUT', 'Already checked out.', 409);
    }

    public static function alreadyResolved(string $message = 'This record has already been resolved.'): self
    {
        return new self('ALREADY_RESOLVED', $message, 409);
    }

    public static function insufficientBalance(): self
    {
        return new self('INSUFFICIENT_BALANCE', 'Insufficient leave balance.', 422);
    }

    public static function requestOverlap(): self
    {
        return new self('REQUEST_OVERLAP', 'Leave request dates overlap an existing request.', 422);
    }

    public static function attachmentRequired(): self
    {
        return new self('ATTACHMENT_REQUIRED', 'An attachment is required for this leave type.', 422);
    }
}
