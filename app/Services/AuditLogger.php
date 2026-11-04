<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    /**
     * @param  array<string, mixed>|null  $changes
     */
    public function log(?User $actor, string $action, ?Model $subject = null, ?array $changes = null): AuditLog
    {
        return AuditLog::query()->create([
            'actor_user_id' => $actor?->id,
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey() !== null ? (string) $subject->getKey() : null,
            'changes' => $changes,
            'ip' => Request::ip(),
            'created_at' => now(),
        ]);
    }
}
