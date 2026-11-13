<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HrEmployeeController;
use App\Http\Controllers\HrSettingsController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SuperAdminController;
use Illuminate\Support\Facades\Route;

// Guest authentication routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

// Logout
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Quick user switcher for demo / local testing
Route::get('/dev/switch/{username}', [AuthController::class, 'switchUser'])->name('dev.switch');

// Authenticated shared routes
Route::middleware('auth')->group(function () {
    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Karyawan (Employee) routes
    Route::middleware('role:karyawan,employee')->group(function () {
        Route::get('/attendance-history', [AttendanceController::class, 'employeeHistory'])->name('attendance.history');
        Route::get('/leave-requests', [LeaveController::class, 'employeeRequests'])->name('leave.requests');
    });

    // Manager routes
    Route::middleware('role:manager')->prefix('manager')->name('manager.')->group(function () {
        Route::get('/attendance-reviews', [AttendanceController::class, 'reviewQueue'])->name('attendance-reviews');
        Route::post('/attendance-reviews/{id}', [AttendanceController::class, 'submitReview'])->name('attendance-reviews.submit');
        Route::get('/leave-approvals', [LeaveController::class, 'approvalQueue'])->name('leave-approvals');
        Route::post('/leave-approvals/{id}', [LeaveController::class, 'submitDecision'])->name('leave-approvals.submit');
        Route::get('/team-history', [AttendanceController::class, 'managerTeamHistory'])->name('team-history');
    });

    // HR routes (Accessible by Admin HR and Super Admin)
    Route::middleware('role:admin_hr')->prefix('hr')->name('hr.')->group(function () {
        // Employees
        Route::get('/employees', [HrEmployeeController::class, 'index'])->name('employees');
        Route::get('/employees/create', [HrEmployeeController::class, 'create'])->name('employees.create');
        Route::post('/employees', [HrEmployeeController::class, 'store'])->name('employees.store');
        Route::get('/employees/{id}', [HrEmployeeController::class, 'show'])->name('employees.show');
        Route::put('/employees/{id}', [HrEmployeeController::class, 'update'])->name('employees.update');
        Route::post('/employees/{id}/face-reference', [HrEmployeeController::class, 'updateFaceReference'])->name('employees.face-reference');

        // Review & Approvals (Company-wide)
        Route::get('/attendance-reviews', [AttendanceController::class, 'reviewQueue'])->name('attendance-reviews');
        Route::post('/attendance-reviews/{id}', [AttendanceController::class, 'submitReview'])->name('attendance-reviews.submit');
        Route::get('/leave-requests', [LeaveController::class, 'approvalQueue'])->name('leave-requests');
        Route::post('/leave-requests/{id}', [LeaveController::class, 'submitDecision'])->name('leave-requests.submit');

        // Settings & Configurations
        Route::get('/shifts', [HrSettingsController::class, 'shifts'])->name('shifts');
        Route::post('/shifts', [HrSettingsController::class, 'storeShift'])->name('shifts.store');
        Route::put('/shifts/{id}', [HrSettingsController::class, 'updateShift'])->name('shifts.update');

        Route::get('/leave-settings', [HrSettingsController::class, 'leaveSettings'])->name('leave-settings');
        Route::post('/leave-types', [HrSettingsController::class, 'storeLeaveType'])->name('leave-types.store');
        Route::put('/leave-types/{id}', [HrSettingsController::class, 'updateLeaveType'])->name('leave-types.update');
        Route::post('/leave-balances', [HrSettingsController::class, 'saveBalance'])->name('leave-balances.save');

        // Reports
        Route::get('/reports', [HrSettingsController::class, 'reports'])->name('reports');

        // Alias for HR users management
        Route::get('/users', fn () => redirect()->route('admin.users'))->name('users');
    });

    // User & Account Management routes (Accessible by HR Admin and Super Admin)
    Route::middleware('role:admin_hr,super_admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [SuperAdminController::class, 'users'])->name('users');
        Route::post('/users', [SuperAdminController::class, 'storeUser'])->name('users.store');
        Route::put('/users/{id}', [SuperAdminController::class, 'updateUser'])->name('users.update');
        Route::delete('/users/{id}', [SuperAdminController::class, 'destroyUser'])->name('users.destroy');
    });
});
