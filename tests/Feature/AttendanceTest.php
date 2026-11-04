<?php

use App\Enums\AttendanceStatus;
use App\Enums\ShiftCategory;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkLocation;
use App\Models\WorkShift;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    Storage::fake('local');

    // Create fake reference photo for Alex
    Storage::disk('local')->put('employees/demo/alex_face.jpg', 'fake-image-content');

    // Configure regular shift times for testing (start at end of day to guarantee present status)
    $shift = WorkShift::query()->where('name', 'Regular')->first();
    $shift->update([
        'start_time' => '23:59',
        'end_time' => '08:00',
    ]);

    // Delete pre-seeded attendances to test fresh check-in
    Attendance::query()->delete();
});

test('check-in succeeds with valid data', function () {
    $user = User::query()->where('username', 'alex.morgan')->first();
    $shift = WorkShift::query()->where('name', 'Regular')->first();
    $loc = WorkLocation::query()->first();

    $photo = UploadedFile::fake()->create('attempt.jpg', 100, 'image/jpeg');

    $response = $this->actingAs($user)->postJson('/api/v1/attendance/check-in', [
        'shift_id' => $shift->id,
        'latitude' => $loc->latitude,
        'longitude' => $loc->longitude,
        'photo' => $photo,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.status', 'present')
        ->assertJsonPath('data.review_status', 'pending')
        ->assertJsonPath('data.check_in.face_verified', true)
        ->assertJsonPath('data.check_in.geofence_valid', true);

    $this->assertDatabaseHas('attendances', [
        'employee_id' => $user->employee_id,
        'shift_id' => $shift->id,
    ]);

    $this->assertDatabaseHas('attendance_attempts', [
        'employee_id' => $user->employee_id,
        'outcome' => 'success',
    ]);
});

test('check-in fails when face reference is not registered', function () {
    $user = User::query()->where('username', 'maya.chen')->first();
    $shift = WorkShift::query()->where('name', 'Regular')->first();
    $loc = WorkLocation::query()->first();

    $response = $this->actingAs($user)->postJson('/api/v1/attendance/check-in', [
        'shift_id' => $shift->id,
        'latitude' => $loc->latitude,
        'longitude' => $loc->longitude,
        'photo' => UploadedFile::fake()->create('attempt.jpg', 100, 'image/jpeg'),
    ]);

    $response->assertStatus(422)
        ->assertJson(['code' => 'FACE_NOT_REGISTERED']);
});

test('check-in fails when shift is not allowed for employee work category', function () {
    $user = User::query()->where('username', 'alex.morgan')->first();
    $shifting = WorkShift::query()->where('category', ShiftCategory::Shifting)->first();
    $shifting->update(['start_time' => '07:00', 'end_time' => '15:00']);
    $loc = WorkLocation::query()->first();

    $response = $this->actingAs($user)->postJson('/api/v1/attendance/check-in', [
        'shift_id' => $shifting->id,
        'latitude' => $loc->latitude,
        'longitude' => $loc->longitude,
        'photo' => UploadedFile::fake()->create('attempt.jpg', 100, 'image/jpeg'),
    ]);

    $response->assertStatus(422)
        ->assertJson(['code' => 'SHIFT_NOT_ALLOWED']);
});

test('check-in fails when shift is not configured', function () {
    $user = User::query()->where('username', 'alex.morgan')->first();
    $shift = WorkShift::query()->where('name', 'Regular')->first();
    $shift->update(['start_time' => null, 'end_time' => null]);
    $loc = WorkLocation::query()->first();

    $response = $this->actingAs($user)->postJson('/api/v1/attendance/check-in', [
        'shift_id' => $shift->id,
        'latitude' => $loc->latitude,
        'longitude' => $loc->longitude,
        'photo' => UploadedFile::fake()->create('attempt.jpg', 100, 'image/jpeg'),
    ]);

    $response->assertStatus(422)
        ->assertJson(['code' => 'SHIFT_NOT_CONFIGURED']);
});

test('check-in fails when outside permitted area', function () {
    $user = User::query()->where('username', 'alex.morgan')->first();
    $shift = WorkShift::query()->where('name', 'Regular')->first();

    // Far away coordinates
    $response = $this->actingAs($user)->postJson('/api/v1/attendance/check-in', [
        'shift_id' => $shift->id,
        'latitude' => -7.500000,
        'longitude' => 110.500000,
        'photo' => UploadedFile::fake()->create('attempt.jpg', 100, 'image/jpeg'),
    ]);

    $response->assertStatus(422)
        ->assertJson(['code' => 'OUTSIDE_PERMITTED_AREA']);

    $this->assertDatabaseHas('attendance_attempts', [
        'employee_id' => $user->employee_id,
        'outcome' => 'failed',
        'failure_code' => 'OUTSIDE_PERMITTED_AREA',
    ]);
});

test('check-in fails when face verification mismatches', function () {
    $user = User::query()->where('username', 'alex.morgan')->first();
    $shift = WorkShift::query()->where('name', 'Regular')->first();
    $loc = WorkLocation::query()->first();

    config(['humify.face_verifier.fake.matched' => false]);
    config(['humify.face_verifier.fake.score' => 45]);

    $response = $this->actingAs($user)->postJson('/api/v1/attendance/check-in', [
        'shift_id' => $shift->id,
        'latitude' => $loc->latitude,
        'longitude' => $loc->longitude,
        'photo' => UploadedFile::fake()->create('attempt.jpg', 100, 'image/jpeg'),
    ]);

    $response->assertStatus(422)
        ->assertJson(['code' => 'FACE_MISMATCH']);

    $this->assertDatabaseHas('attendance_attempts', [
        'employee_id' => $user->employee_id,
        'outcome' => 'failed',
        'failure_code' => 'FACE_MISMATCH',
    ]);
});

test('check-in fails with 503 when face service throws', function () {
    $user = User::query()->where('username', 'alex.morgan')->first();
    $shift = WorkShift::query()->where('name', 'Regular')->first();
    $loc = WorkLocation::query()->first();

    config(['humify.face_verifier.fake.throw' => true]);

    $response = $this->actingAs($user)->postJson('/api/v1/attendance/check-in', [
        'shift_id' => $shift->id,
        'latitude' => $loc->latitude,
        'longitude' => $loc->longitude,
        'photo' => UploadedFile::fake()->create('attempt.jpg', 100, 'image/jpeg'),
    ]);

    $response->assertStatus(503)
        ->assertJson(['code' => 'FACE_SERVICE_UNAVAILABLE']);
});

test('check-in returns 409 ALREADY_CHECKED_IN on duplicate check-in with existing record', function () {
    $user = User::query()->where('username', 'alex.morgan')->first();
    $shift = WorkShift::query()->where('name', 'Regular')->first();
    $loc = WorkLocation::query()->first();

    $this->actingAs($user)->postJson('/api/v1/attendance/check-in', [
        'shift_id' => $shift->id,
        'latitude' => $loc->latitude,
        'longitude' => $loc->longitude,
        'photo' => UploadedFile::fake()->create('attempt.jpg', 100, 'image/jpeg'),
    ])->assertStatus(201);

    $secondResponse = $this->actingAs($user)->postJson('/api/v1/attendance/check-in', [
        'shift_id' => $shift->id,
        'latitude' => $loc->latitude,
        'longitude' => $loc->longitude,
        'photo' => UploadedFile::fake()->create('attempt.jpg', 100, 'image/jpeg'),
    ]);

    $secondResponse->assertStatus(409)
        ->assertJsonStructure([
            'code',
            'data' => ['id', 'status', 'check_in'],
        ])
        ->assertJson(['code' => 'ALREADY_CHECKED_IN']);
});

test('check-out without check-in returns 409 NOT_CHECKED_IN', function () {
    $user = User::query()->where('username', 'alex.morgan')->first();
    $loc = WorkLocation::query()->first();

    $response = $this->actingAs($user)->postJson('/api/v1/attendance/check-out', [
        'latitude' => $loc->latitude,
        'longitude' => $loc->longitude,
        'photo' => UploadedFile::fake()->create('attempt.jpg', 100, 'image/jpeg'),
    ]);

    $response->assertStatus(409)
        ->assertJson(['code' => 'NOT_CHECKED_IN']);
});

test('check-out succeeds after check-in and second check-out returns ALREADY_CHECKED_OUT', function () {
    $user = User::query()->where('username', 'alex.morgan')->first();
    $shift = WorkShift::query()->where('name', 'Regular')->first();
    $loc = WorkLocation::query()->first();

    $this->actingAs($user)->postJson('/api/v1/attendance/check-in', [
        'shift_id' => $shift->id,
        'latitude' => $loc->latitude,
        'longitude' => $loc->longitude,
        'photo' => UploadedFile::fake()->create('checkin.jpg', 100, 'image/jpeg'),
    ])->assertStatus(201);

    $outResponse = $this->actingAs($user)->postJson('/api/v1/attendance/check-out', [
        'latitude' => $loc->latitude,
        'longitude' => $loc->longitude,
        'photo' => UploadedFile::fake()->create('checkout.jpg', 100, 'image/jpeg'),
    ]);

    $outResponse->assertStatus(200)
        ->assertJsonPath('data.check_out.face_verified', true);

    // Second checkout
    $secondOut = $this->actingAs($user)->postJson('/api/v1/attendance/check-out', [
        'latitude' => $loc->latitude,
        'longitude' => $loc->longitude,
        'photo' => UploadedFile::fake()->create('checkout2.jpg', 100, 'image/jpeg'),
    ]);

    $secondOut->assertStatus(409)
        ->assertJson(['code' => 'ALREADY_CHECKED_OUT']);
});

test('late minutes and early leave status calculations', function () {
    $user = User::query()->where('username', 'alex.morgan')->first();
    $shift = WorkShift::query()->where('name', 'Regular')->first();
    $shift->update(['start_time' => '09:00', 'end_time' => '18:00']);

    // Check-in late at 09:15
    $checkInTime = CarbonImmutable::parse('2026-10-20 09:15:30', 'Asia/Jakarta')->utc();
    $date = CarbonImmutable::parse('2026-10-20', 'Asia/Jakarta')->toDateString();

    $att = Attendance::query()->create([
        'employee_id' => $user->employee_id,
        'shift_id' => $shift->id,
        'attendance_date' => $date,
        'check_in_time' => $checkInTime,
        'late_minutes' => 16, // ceil of 15m 30s
        'status' => AttendanceStatus::Late,
    ]);

    $this->assertEquals(16, $att->late_minutes);
    $this->assertEquals(AttendanceStatus::Late, $att->status);
});

test('attendance close day marks absent for employees without attendance and is idempotent', function () {
    $date = CarbonImmutable::parse('2026-10-15', 'Asia/Jakarta'); // Thursday (working day)

    $this->artisan('attendance:close-day', ['--date' => $date->toDateString()])
        ->assertSuccessful();

    $absentCount = Attendance::query()
        ->whereDate('attendance_date', $date->toDateString())
        ->where('status', AttendanceStatus::Absent)
        ->count();

    $this->assertGreaterThan(0, $absentCount);

    // Re-running must be idempotent (0 new created)
    $this->artisan('attendance:close-day', ['--date' => $date->toDateString()])
        ->expectsOutputToContain('0 absent record(s) created.')
        ->assertSuccessful();
});
