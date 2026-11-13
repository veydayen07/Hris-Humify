<?php

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('guest is redirected to login when visiting protected pages', function () {
    $response = get('/dashboard');
    $response->assertRedirect('/login');
});

test('login page is accessible and displays Humify brand', function () {
    $response = get('/login');
    $response->assertStatus(200);
    $response->assertSee('Humify');
    $response->assertSee('A clearer view of your workday.');
});

test('employee can login and access permitted personal pages', function () {
    $user = User::where('username', 'alex.morgan')->first();

    actingAs($user)
        ->get('/dashboard')
        ->assertStatus(200)
        ->assertSee('Good morning, Alex');

    actingAs($user)
        ->get('/profile')
        ->assertStatus(200)
        ->assertSee('Personal Information');

    actingAs($user)
        ->get('/attendance-history')
        ->assertStatus(200)
        ->assertSee('My attendance history');

    actingAs($user)
        ->get('/leave-requests')
        ->assertStatus(200)
        ->assertSee('My leave & requests');

    $updateResponse = actingAs($user)
        ->put('/profile', [
            'phone' => '081234567890',
            'email' => 'alex.updated@humify.test',
            'address' => 'Jl. Baru No. 10',
            'birth_place' => 'Jakarta',
            'birth_date' => '1994-11-18',
            'gender' => 'L',
        ]);

    $updateResponse->assertRedirect('/profile');
    $updateResponse->assertSessionHas('success');
    expect($user->employee->fresh()->phone)->toBe('081234567890')
        ->and($user->employee->fresh()->email)->toBe('alex.updated@humify.test');
});

test('employee is forbidden from manager, hr, and admin pages', function () {
    $user = User::where('username', 'alex.morgan')->first();

    actingAs($user)
        ->get('/manager/attendance-reviews')
        ->assertStatus(403);

    actingAs($user)
        ->get('/hr/employees')
        ->assertStatus(403);

    actingAs($user)
        ->get('/admin/users')
        ->assertStatus(403);
});

test('manager can access manager review and approval pages but not hr settings or admin users', function () {
    $user = User::where('username', 'maya.chen')->first();

    actingAs($user)
        ->get('/dashboard')
        ->assertStatus(200)
        ->assertSee('Manager Workspace');

    actingAs($user)
        ->get('/manager/attendance-reviews')
        ->assertStatus(200);

    actingAs($user)
        ->get('/manager/leave-approvals')
        ->assertStatus(200);

    actingAs($user)
        ->get('/manager/team-history')
        ->assertStatus(200);

    // Forbidden from HR and Super Admin areas
    actingAs($user)
        ->get('/hr/shifts')
        ->assertStatus(403);

    actingAs($user)
        ->get('/admin/users')
        ->assertStatus(403);
});

test('hr admin can access hr management pages and user accounts management', function () {
    $user = User::where('username', 'sofia.patel')->first();

    actingAs($user)
        ->get('/dashboard')
        ->assertStatus(200)
        ->assertSee('HR Workspace');

    actingAs($user)
        ->get('/hr/employees')
        ->assertStatus(200);

    actingAs($user)
        ->get('/hr/attendance-reviews')
        ->assertStatus(200);

    actingAs($user)
        ->get('/hr/leave-requests')
        ->assertStatus(200);

    actingAs($user)
        ->get('/hr/shifts')
        ->assertStatus(200);

    actingAs($user)
        ->get('/hr/leave-settings')
        ->assertStatus(200);

    actingAs($user)
        ->get('/hr/reports')
        ->assertStatus(200);

    // HR Admin can access user management
    actingAs($user)
        ->get('/admin/users')
        ->assertStatus(200)
        ->assertSee('Account & Role Management');
});

test('hr admin can manage users including create, update, and delete', function () {
    $hr = User::where('username', 'sofia.patel')->first();

    // Create a temporary employee for user account testing
    $employee = Employee::create([
        'id' => (string) Str::uuid(),
        'employee_code' => 'EMP-TEST-'.rand(1000, 9999),
        'full_name' => 'Testing User Creation',
        'email' => 'test.user.'.rand(1000, 9999).'@humify.test',
        'gender' => 'L',
        'department_id' => 1,
        'position_id' => 1,
        'employment_type' => 'tetap',
        'employee_status' => 'aktif',
        'is_shift_worker' => false,
        'hire_date' => '2026-01-01',
    ]);

    $tempUsername = 'test.user.'.rand(100, 999);

    // HR creates a new user
    $createResponse = actingAs($hr)
        ->post('/admin/users', [
            'employee_id' => $employee->id,
            'username' => $tempUsername,
            'password' => 'secret123',
            'role' => 'employee',
        ]);

    $createResponse->assertRedirect('/admin/users');
    $createResponse->assertSessionHas('success');

    $createdUser = User::where('username', $tempUsername)->first();
    expect($createdUser)->not->toBeNull();

    // HR updates the user
    $updateResponse = actingAs($hr)
        ->put("/admin/users/{$createdUser->id}", [
            'username' => $tempUsername.'_renamed',
            'role' => 'manager',
            'is_active' => '1',
        ]);

    $updateResponse->assertRedirect('/admin/users');
    $updateResponse->assertSessionHas('success');
    expect($createdUser->fresh()->username)->toBe($tempUsername.'_renamed');

    // HR deletes the user
    $deleteResponse = actingAs($hr)
        ->delete("/admin/users/{$createdUser->id}");

    $deleteResponse->assertRedirect('/admin/users');
    $deleteResponse->assertSessionHas('success');
    expect(User::where('id', $createdUser->id)->first())->toBeNull();

    // Clean up temporary employee
    $employee->delete();
});

test('super admin can access all hr features and accounts management', function () {
    $user = User::where('username', 'daniel.brooks')->first();

    actingAs($user)
        ->get('/admin/users')
        ->assertStatus(200)
        ->assertSee('Account & Role Management');

    actingAs($user)
        ->get('/hr/employees')
        ->assertStatus(200);

    actingAs($user)
        ->get('/hr/shifts')
        ->assertStatus(200);
});
