<?php

use App\Enums\ReviewStatus;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    Storage::fake('local');
});

test('manager only sees direct subordinates with team scope', function () {
    $maya = User::query()->where('username', 'maya.chen')->first();

    $response = $this->actingAs($maya)->getJson('/api/v1/attendance/records?scope=team');

    $response->assertStatus(200);
    $records = $response->json('data');

    foreach ($records as $record) {
        $this->assertEquals('EMP-001', $record['employee_code']); // Alex Morgan
    }
});

test('employee cannot use team or company scope', function () {
    $alex = User::query()->where('username', 'alex.morgan')->first();

    $this->actingAs($alex)->getJson('/api/v1/attendance/records?scope=team')
        ->assertStatus(403)
        ->assertJson(['code' => 'FORBIDDEN']);

    $this->actingAs($alex)->getJson('/api/v1/attendance/records?scope=company')
        ->assertStatus(403)
        ->assertJson(['code' => 'FORBIDDEN']);
});

test('no one can review their own attendance record', function () {
    $maya = User::query()->where('username', 'maya.chen')->first();

    // Create attendance for Maya
    $att = Attendance::query()->create([
        'employee_id' => $maya->employee_id,
        'attendance_date' => now()->toDateString(),
        'review_status' => ReviewStatus::Pending,
    ]);

    $this->actingAs($maya)->postJson("/api/v1/attendance/records/{$att->id}/approve", [
        'note' => 'Approved myself',
    ])->assertStatus(403)->assertJson(['code' => 'FORBIDDEN']);

    $this->actingAs($maya)->postJson("/api/v1/attendance/records/{$att->id}/reject", [
        'reason' => 'Reject myself',
    ])->assertStatus(403)->assertJson(['code' => 'FORBIDDEN']);
});

test('manager can approve attendance of subordinate', function () {
    $maya = User::query()->where('username', 'maya.chen')->first();
    $alex = User::query()->where('username', 'alex.morgan')->first();

    $att = Attendance::query()->where('employee_id', $alex->employee_id)
        ->where('review_status', ReviewStatus::Pending)
        ->first();

    $response = $this->actingAs($maya)->postJson("/api/v1/attendance/records/{$att->id}/approve", [
        'note' => 'Looks good',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.review_status', 'approved')
        ->assertJsonPath('data.review_note', 'Looks good');
});

test('reject attendance fails without reason', function () {
    $maya = User::query()->where('username', 'maya.chen')->first();
    $alex = User::query()->where('username', 'alex.morgan')->first();

    $att = Attendance::query()->where('employee_id', $alex->employee_id)
        ->where('review_status', ReviewStatus::Pending)
        ->first();

    $response = $this->actingAs($maya)->postJson("/api/v1/attendance/records/{$att->id}/reject", [
        'reason' => '',
    ]);

    $response->assertStatus(422)
        ->assertJson(['code' => 'VALIDATION_ERROR']);
});

test('cannot approve or reject already resolved attendance record', function () {
    $maya = User::query()->where('username', 'maya.chen')->first();
    $alex = User::query()->where('username', 'alex.morgan')->first();

    $att = Attendance::query()->where('employee_id', $alex->employee_id)
        ->where('review_status', ReviewStatus::Approved)
        ->first();

    $response = $this->actingAs($maya)->postJson("/api/v1/attendance/records/{$att->id}/approve", [
        'note' => 'Re-approving',
    ]);

    $response->assertStatus(409)
        ->assertJson(['code' => 'ALREADY_RESOLVED']);
});

test('record owner cannot access photo signed url', function () {
    $alex = User::query()->where('username', 'alex.morgan')->first();
    $att = Attendance::query()->where('employee_id', $alex->employee_id)->first();

    $response = $this->actingAs($alex)->getJson("/api/v1/attendance/records/{$att->id}/photo");

    $response->assertStatus(403)
        ->assertJson(['code' => 'FORBIDDEN']);
});

