<?php

use App\Enums\AttendanceStatus;
use App\Enums\LeaveRequestStatus;
use App\Models\Attendance;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    Storage::fake('local');
});

test('leave submission does not change balance while pending', function () {
    $alex = User::query()->where('username', 'alex.morgan')->first();
    $annual = LeaveType::query()->where('name', 'Annual Leave')->first();
    $year = (int) now()->year;

    $initialBalance = LeaveBalance::query()
        ->where('employee_id', $alex->employee_id)
        ->where('leave_type_id', $annual->id)
        ->where('year', $year)
        ->first();

    $initialUsed = (float) $initialBalance->used_days;

    $mon = CarbonImmutable::now()->addWeeks(3)->next(CarbonImmutable::MONDAY);
    $nextMonday = $mon->toDateString();
    $nextTuesday = $mon->addDay()->toDateString();

    $response = $this->actingAs($alex)->postJson('/api/v1/leave/requests', [
        'leave_type_id' => $annual->id,
        'start_date' => $nextMonday,
        'end_date' => $nextTuesday,
        'reason' => 'Short trip',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.status', 'pending');

    $balanceAfter = $initialBalance->fresh();
    $this->assertEquals($initialUsed, (float) $balanceAfter->used_days);
});

test('leave submission fails when dates overlap with existing pending or approved request', function () {
    $alex = User::query()->where('username', 'alex.morgan')->first();
    $annual = LeaveType::query()->where('name', 'Annual Leave')->first();

    // In seeder, Alex already has a pending leave request for $today->addDays(5) to $today->addDays(6)
    $existing = LeaveRequest::query()->where('employee_id', $alex->employee_id)->first();

    $response = $this->actingAs($alex)->postJson('/api/v1/leave/requests', [
        'leave_type_id' => $annual->id,
        'start_date' => $existing->start_date->toDateString(),
        'end_date' => $existing->end_date->toDateString(),
        'reason' => 'Overlapping request',
    ]);

    $response->assertStatus(422)
        ->assertJson(['code' => 'REQUEST_OVERLAP']);
});

test('leave submission fails when attachment is required but not provided', function () {
    $alex = User::query()->where('username', 'alex.morgan')->first();
    $sick = LeaveType::query()->where('name', 'Sick Leave')->first();

    $nextFriday = CarbonImmutable::now()->next(CarbonImmutable::FRIDAY)->toDateString();

    $response = $this->actingAs($alex)->postJson('/api/v1/leave/requests', [
        'leave_type_id' => $sick->id,
        'start_date' => $nextFriday,
        'end_date' => $nextFriday,
        'reason' => 'Fever',
    ]);

    $response->assertStatus(422)
        ->assertJson(['code' => 'ATTACHMENT_REQUIRED']);

    // With attachment, it should succeed
    $file = UploadedFile::fake()->create('doctor_note.pdf', 100, 'application/pdf');

    $successResponse = $this->actingAs($alex)->postJson('/api/v1/leave/requests', [
        'leave_type_id' => $sick->id,
        'start_date' => $nextFriday,
        'end_date' => $nextFriday,
        'reason' => 'Fever',
        'attachment' => $file,
    ]);

    $successResponse->assertStatus(201)
        ->assertJsonPath('data.has_attachment', true);
});

test('leave approval deducts balance and creates attendances without overwriting check-in records', function () {
    $alex = User::query()->where('username', 'alex.morgan')->first();
    $maya = User::query()->where('username', 'maya.chen')->first();
    $annual = LeaveType::query()->where('name', 'Annual Leave')->first();
    $year = (int) now()->year;

    $balance = LeaveBalance::query()
        ->where('employee_id', $alex->employee_id)
        ->where('leave_type_id', $annual->id)
        ->where('year', $year)
        ->first();

    $initialUsed = (float) $balance->used_days;

    // Pick upcoming Monday and Tuesday
    $mon = CarbonImmutable::now()->next(CarbonImmutable::MONDAY);
    $tue = $mon->addDay();

    // Simulate that Alex already checked in on Monday
    Attendance::query()->create([
        'employee_id' => $alex->employee_id,
        'attendance_date' => $mon->toDateString(),
        'check_in_time' => $mon->setTime(8, 50)->utc(),
        'status' => AttendanceStatus::Present,
    ]);

    $request = LeaveRequest::query()->create([
        'employee_id' => $alex->employee_id,
        'leave_type_id' => $annual->id,
        'start_date' => $mon->toDateString(),
        'end_date' => $tue->toDateString(),
        'total_days' => 2,
        'status' => LeaveRequestStatus::Pending,
    ]);

    $response = $this->actingAs($maya)->postJson("/api/v1/leave/requests/{$request->id}/approve");

    $response->assertStatus(200)
        ->assertJsonPath('data.status', 'approved')
        ->assertJsonPath('meta.skipped_dates', [$mon->toDateString()]);

    // Balance deducted by total_days
    $this->assertEquals($initialUsed + 2, (float) $balance->fresh()->used_days);

    // Monday attendance should NOT be overwritten (still present)
    $monAtt = Attendance::query()
        ->where('employee_id', $alex->employee_id)
        ->whereDate('attendance_date', $mon->toDateString())
        ->first();
    $this->assertEquals(AttendanceStatus::Present, $monAtt->status);

    // Tuesday attendance should be created as on_leave
    $tueAtt = Attendance::query()
        ->where('employee_id', $alex->employee_id)
        ->whereDate('attendance_date', $tue->toDateString())
        ->first();
    $this->assertEquals(AttendanceStatus::OnLeave, $tueAtt->status);
});

test('leave approval fails with 422 INSUFFICIENT_BALANCE when balance is not enough', function () {
    $alex = User::query()->where('username', 'alex.morgan')->first();
    $maya = User::query()->where('username', 'maya.chen')->first();
    $annual = LeaveType::query()->where('name', 'Annual Leave')->first();
    $year = (int) now()->year;

    // Set used_days = 12 so remaining is 0
    LeaveBalance::query()
        ->where('employee_id', $alex->employee_id)
        ->where('leave_type_id', $annual->id)
        ->where('year', $year)
        ->update(['total_days' => 12, 'used_days' => 12]);

    $request = LeaveRequest::query()->create([
        'employee_id' => $alex->employee_id,
        'leave_type_id' => $annual->id,
        'start_date' => CarbonImmutable::now()->next(CarbonImmutable::WEDNESDAY)->toDateString(),
        'end_date' => CarbonImmutable::now()->next(CarbonImmutable::THURSDAY)->toDateString(),
        'total_days' => 2,
        'status' => LeaveRequestStatus::Pending,
    ]);

    $response = $this->actingAs($maya)->postJson("/api/v1/leave/requests/{$request->id}/approve");

    $response->assertStatus(422)
        ->assertJson(['code' => 'INSUFFICIENT_BALANCE']);
});

test('leave cancel can only be done by owner while pending', function () {
    $alex = User::query()->where('username', 'alex.morgan')->first();
    $maya = User::query()->where('username', 'maya.chen')->first();
    $annual = LeaveType::query()->where('name', 'Annual Leave')->first();

    $request = LeaveRequest::query()->create([
        'employee_id' => $alex->employee_id,
        'leave_type_id' => $annual->id,
        'start_date' => CarbonImmutable::now()->next(CarbonImmutable::MONDAY)->toDateString(),
        'end_date' => CarbonImmutable::now()->next(CarbonImmutable::MONDAY)->toDateString(),
        'total_days' => 1,
        'status' => LeaveRequestStatus::Pending,
    ]);

    // Maya (not owner) tries to cancel -> 403
    $this->actingAs($maya)->postJson("/api/v1/leave/requests/{$request->id}/cancel")
        ->assertStatus(403);

    // Alex (owner) cancels -> 200
    $this->actingAs($alex)->postJson("/api/v1/leave/requests/{$request->id}/cancel")
        ->assertStatus(200)
        ->assertJsonPath('data.status', 'canceled');

    // Canceling approved request is not supported on MVP -> 409
    $approvedRequest = LeaveRequest::query()->create([
        'employee_id' => $alex->employee_id,
        'leave_type_id' => $annual->id,
        'start_date' => CarbonImmutable::now()->subDays(20)->toDateString(),
        'end_date' => CarbonImmutable::now()->subDays(20)->toDateString(),
        'total_days' => 1,
        'status' => LeaveRequestStatus::Approved,
    ]);

    $this->actingAs($alex)->postJson("/api/v1/leave/requests/{$approvedRequest->id}/cancel")
        ->assertStatus(409)
        ->assertJson(['code' => 'ALREADY_RESOLVED']);
});
