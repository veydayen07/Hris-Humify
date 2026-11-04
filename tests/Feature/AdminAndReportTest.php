<?php

use App\Enums\AttendanceStatus;
use App\Enums\EmployeeStatus;
use App\Enums\ReviewStatus;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

test('hr_admin cannot access accounts management', function () {
    $sofia = User::query()->where('username', 'sofia.patel')->first();

    $this->actingAs($sofia)->getJson('/api/v1/accounts')
        ->assertStatus(403)
        ->assertJson(['code' => 'FORBIDDEN']);

    $alex = User::query()->where('username', 'alex.morgan')->first();

    $this->actingAs($sofia)->patchJson("/api/v1/accounts/{$alex->id}", [
        'role' => 'manager',
    ])->assertStatus(403)->assertJson(['code' => 'FORBIDDEN']);
});

test('super admin cannot demote or deactivate own account', function () {
    $daniel = User::query()->where('username', 'daniel.brooks')->first();

    // Demote self
    $this->actingAs($daniel)->patchJson("/api/v1/accounts/{$daniel->id}", [
        'role' => 'employee',
    ])->assertStatus(403)
        ->assertJson(['code' => 'FORBIDDEN']);

    // Deactivate self
    $this->actingAs($daniel)->patchJson("/api/v1/accounts/{$daniel->id}", [
        'is_active' => false,
    ])->assertStatus(403)
        ->assertJson(['code' => 'FORBIDDEN']);
});

test('deactivating employee requires confirm and deactivates account with tokens revoked', function () {
    $sofia = User::query()->where('username', 'sofia.patel')->first();
    $alex = User::query()->where('username', 'alex.morgan')->first();

    // Create token for Alex
    $alex->createToken('mobile');
    $this->assertCount(1, $alex->tokens);

    // Fail without confirm
    $this->actingAs($sofia)->postJson("/api/v1/employees/{$alex->employee_id}/deactivate", [
        'confirm' => false,
    ])->assertStatus(422);

    // Success with confirm
    $response = $this->actingAs($sofia)->postJson("/api/v1/employees/{$alex->employee_id}/deactivate", [
        'confirm' => true,
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.employee_status', 'inactive');

    $alexUser = $alex->fresh();
    $this->assertFalse($alexUser->is_active);
    $this->assertCount(0, $alexUser->tokens);
});

test('monthly report counts approved records by default and pending when requested', function () {
    $sofia = User::query()->where('username', 'sofia.patel')->first();
    $alex = User::query()->where('username', 'alex.morgan')->first();

    $month = now()->format('Y-m');

    // Default: only approved
    $response = $this->actingAs($sofia)->getJson("/api/v1/reports/attendance-monthly?month={$month}");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'month',
                'summary' => [
                    'present_days',
                    'late_records',
                    'late_minutes',
                    'leave_days',
                    'absent_days',
                    'total_employees',
                ],
                'employees',
            ],
        ]);

    $defaultPresent = $response->json('data.summary.present_days');

    // With include_pending=true
    $pendingResponse = $this->actingAs($sofia)->getJson("/api/v1/reports/attendance-monthly?month={$month}&include_pending=true");

    $pendingResponse->assertStatus(200);
    $withPendingPresent = $pendingResponse->json('data.summary.present_days');

    // Since in seeder Alex has 1 approved and 1 pending present record for this month:
    $this->assertGreaterThanOrEqual($defaultPresent, $withPendingPresent);
});

test('monthly report can be exported as csv', function () {
    $sofia = User::query()->where('username', 'sofia.patel')->first();
    $month = now()->format('Y-m');

    $response = $this->actingAs($sofia)->get("/api/v1/reports/attendance-monthly?month={$month}&format=csv");

    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    $this->assertStringContainsString('Employee Code', $response->getContent());
    $this->assertStringContainsString('Alex Morgan', $response->getContent());
});
