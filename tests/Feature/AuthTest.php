<?php

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    Storage::fake('local');
    RateLimiter::clear('alex.morgan|127.0.0.1');
    RateLimiter::clear('invalid.user|127.0.0.1');
});

test('login succeeds with valid credentials', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'username' => 'alex.morgan',
        'password' => 'Password123!',
        'device_name' => 'Test Mobile',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'token',
                'user' => [
                    'id',
                    'username',
                    'role',
                    'employee' => ['id', 'employee_code', 'full_name'],
                ],
            ],
            'meta',
        ]);
});

test('login fails with generic message for invalid credentials', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'username' => 'alex.morgan',
        'password' => 'WrongPassword',
        'device_name' => 'Test Mobile',
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'code' => 'INVALID_CREDENTIALS',
            'message' => 'Invalid username or password.',
        ]);
});

test('login throttles after 5 failed attempts with 429 and retry_after', function () {
    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/v1/auth/login', [
            'username' => 'alex.morgan',
            'password' => 'WrongPassword',
            'device_name' => 'Test Mobile',
        ])->assertStatus(401);
    }

    $response = $this->postJson('/api/v1/auth/login', [
        'username' => 'alex.morgan',
        'password' => 'WrongPassword',
        'device_name' => 'Test Mobile',
    ]);

    $response->assertStatus(429)
        ->assertJsonStructure([
            'message',
            'code',
            'meta' => ['retry_after'],
        ])
        ->assertJson(['code' => 'TOO_MANY_ATTEMPTS']);
});

test('inactive employee cannot login', function () {
    $alex = User::query()->where('username', 'alex.morgan')->first();
    $alex->employee->update(['employee_status' => EmployeeStatus::Inactive]);

    $response = $this->postJson('/api/v1/auth/login', [
        'username' => 'alex.morgan',
        'password' => 'Password123!',
        'device_name' => 'Test Mobile',
    ]);

    $response->assertStatus(401)
        ->assertJson(['code' => 'INVALID_CREDENTIALS']);
});

test('logout revokes current access token', function () {
    $user = User::query()->where('username', 'alex.morgan')->first();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/auth/logout');

    $response->assertStatus(200);
    $this->assertCount(0, $user->tokens);
});

test('me returns current employee profile', function () {
    $user = User::query()->where('username', 'alex.morgan')->first();

    $response = $this->actingAs($user)->getJson('/api/v1/me');

    $response->assertStatus(200)
        ->assertJsonPath('data.full_name', 'Alex Morgan')
        ->assertJsonPath('data.work_category', 'regular');
});

test('update contact allows only contact fields', function () {
    $user = User::query()->where('username', 'alex.morgan')->first();

    $response = $this->actingAs($user)->patchJson('/api/v1/me/contact', [
        'phone' => '+628123456789',
        'personal_email' => 'new.alex@example.com',
        'address' => 'Jakarta, Indonesia',
        'full_name' => 'Hacked Name', // should not change
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.phone', '+628123456789')
        ->assertJsonPath('data.personal_email', 'new.alex@example.com')
        ->assertJsonPath('data.address', 'Jakarta, Indonesia')
        ->assertJsonPath('data.full_name', 'Alex Morgan');
});

test('employee can register face once and cannot re-register', function () {
    $user = User::query()->where('username', 'maya.chen')->first();
    $user->employee->update([
        'face_reference_photo_url' => null,
        'face_registered_at' => null,
    ]);

    $file = UploadedFile::fake()->create('face.jpg', 100, 'image/jpeg');

    $response = $this->actingAs($user)->postJson('/api/v1/me/face-reference', [
        'photo' => $file,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.face_registered', true);

    // Second time must fail with 409 FACE_ALREADY_REGISTERED
    $secondResponse = $this->actingAs($user)->postJson('/api/v1/me/face-reference', [
        'photo' => $file,
    ]);

    $secondResponse->assertStatus(409)
        ->assertJson(['code' => 'FACE_ALREADY_REGISTERED']);
});
