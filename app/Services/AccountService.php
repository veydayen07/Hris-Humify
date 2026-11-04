<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Exceptions\ApiException;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class AccountService
{
    public function __construct(
        private AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters): LengthAwarePaginator
    {
        $perPage = min(max((int) ($filters['per_page'] ?? 15), 1), 100);

        $query = User::query()->with(['employee.department', 'employee.position']);

        if (! empty($filters['q'])) {
            $q = (string) $filters['q'];
            $query->where(function (Builder $b) use ($q) {
                $b->where('username', 'like', "%{$q}%")
                    ->orWhereHas('employee', function (Builder $empQuery) use ($q) {
                        $empQuery->where('full_name', 'like', "%{$q}%")
                            ->orWhere('employee_code', 'like', "%{$q}%");
                    });
            });
        }

        if (! empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOL));
        }

        return $query->orderBy('username')->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $actor, array $data): User
    {
        $employee = Employee::query()->findOrFail($data['employee_id']);

        if ($employee->user()->exists()) {
            throw ApiException::validation('This employee already has an account.', [
                'employee_id' => ['An account for this employee already exists.'],
            ]);
        }

        $user = User::query()->create([
            'employee_id' => $employee->id,
            'username' => $data['username'],
            'password' => $data['password'],
            'role' => $data['role'] ?? UserRole::Employee->value,
            'is_active' => $data['is_active'] ?? true,
        ]);

        $this->auditLogger->log($actor, 'account.created', $user, [
            'username' => $user->username,
            'role' => $user->role->value,
        ]);

        return $user->fresh(['employee.department', 'employee.position']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $actor, User $targetUser, array $data): User
    {
        // "Tidak boleh menurunkan role atau menonaktifkan akun sendiri."
        if ($actor->id === $targetUser->id) {
            if (isset($data['is_active']) && ! filter_var($data['is_active'], FILTER_VALIDATE_BOOL)) {
                throw ApiException::forbidden('You cannot deactivate your own account.');
            }

            if (isset($data['role']) && $data['role'] !== $actor->role->value) {
                throw ApiException::forbidden('You cannot change your own role.');
            }
        }

        $payload = [];

        if (isset($data['role'])) {
            $payload['role'] = $data['role'];
        }

        if (isset($data['is_active'])) {
            $payload['is_active'] = filter_var($data['is_active'], FILTER_VALIDATE_BOOL);
            if (! $payload['is_active']) {
                $targetUser->tokens()->delete();
            }
        }

        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        $targetUser->update($payload);

        $this->auditLogger->log($actor, 'account.updated', $targetUser, [
            'role' => $targetUser->role->value,
            'is_active' => $targetUser->is_active,
        ]);

        return $targetUser->fresh(['employee.department', 'employee.position']);
    }
}

