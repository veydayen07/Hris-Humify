<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProfileService
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateContact(User $user, array $data): Employee
    {
        $employee = $user->employee;
        $allowed = [
            'phone',
            'personal_email',
            'address',
            'emergency_contact_name',
            'emergency_contact_phone',
            'emergency_contact_relationship',
        ];

        $payload = collect($data)->only($allowed)->all();
        $employee->fill($payload);
        $employee->save();

        return $employee->fresh(['department', 'position', 'supervisor']);
    }

    public function registerFaceReference(User $user, UploadedFile $photo): Employee
    {
        $employee = $user->employee;

        if ($employee->isFaceRegistered()) {
            throw ApiException::faceAlreadyRegistered();
        }

        $path = $photo->store("employees/{$employee->id}/face", 'local');

        $employee->forceFill([
            'face_reference_photo_url' => $path,
            'face_registered_at' => now(),
            'face_registered_by' => $user->id,
        ])->save();

        $this->auditLogger->log($user, 'face_reference.registered', $employee, [
            'path' => $path,
        ]);

        return $employee->fresh();
    }

    public function replaceFaceReference(User $actor, Employee $employee, UploadedFile $photo): Employee
    {
        $oldPath = $employee->face_reference_photo_url;
        $path = $photo->store("employees/{$employee->id}/face", 'local');

        $employee->forceFill([
            'face_reference_photo_url' => $path,
            'face_registered_at' => now(),
            'face_registered_by' => $actor->id,
        ])->save();

        if ($oldPath && Storage::disk('local')->exists($oldPath)) {
            Storage::disk('local')->delete($oldPath);
        }

        $this->auditLogger->log($actor, 'face_reference.replaced', $employee, [
            'old_path' => $oldPath,
            'new_path' => $path,
        ]);

        return $employee->fresh(['faceRegisteredBy']);
    }
}
