<?php

use App\Http\Controllers\Api\v1\AccountController;
use App\Http\Controllers\Api\v1\AttendanceController;
use App\Http\Controllers\Api\v1\AuthController;
use App\Http\Controllers\Api\v1\DashboardController;
use App\Http\Controllers\Api\v1\EmployeeController;
use App\Http\Controllers\Api\v1\LeaveController;
use App\Http\Controllers\Api\v1\MasterDataController;
use App\Http\Controllers\Api\v1\SettingsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public routes
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

    // Signed URL download routes for private files
    Route::get('/attendance/records/{id}/photo-file', [AttendanceController::class, 'downloadPhotoFile'])
        ->name('api.v1.attendance.photo-file');
    Route::get('/leave/requests/{id}/attachment-file', [LeaveController::class, 'downloadAttachmentFile'])
        ->name('api.v1.leave.attachment-file');
    Route::get('/employees/{id}/face-photo', [EmployeeController::class, 'downloadFacePhoto'])
        ->name('api.v1.employees.face-photo');

    // Authenticated routes
    Route::middleware('auth:sanctum')->group(function () {
        // Auth & Profile
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::patch('/me/contact', [AuthController::class, 'updateContact']);
        Route::post('/me/face-reference', [AuthController::class, 'registerFaceReference']);

        // Attendance
        Route::get('/attendance/today', [AttendanceController::class, 'today']);
        Route::get('/shifts/available', [AttendanceController::class, 'availableShifts']);
        Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn'])->middleware('throttle:10,1');
        Route::post('/attendance/check-out', [AttendanceController::class, 'checkOut'])->middleware('throttle:10,1');
        Route::get('/attendance/records', [AttendanceController::class, 'records']);
        Route::get('/attendance/records/{id}', [AttendanceController::class, 'record']);
        Route::get('/attendance/records/{id}/photo', [AttendanceController::class, 'photo']);
        Route::post('/attendance/records/{id}/approve', [AttendanceController::class, 'approve']);
        Route::post('/attendance/records/{id}/reject', [AttendanceController::class, 'reject']);

        // Leave
        Route::get('/leave/types', [LeaveController::class, 'types']);
        Route::post('/leave/types', [LeaveController::class, 'createType']);
        Route::patch('/leave/types/{id}', [LeaveController::class, 'updateType']);
        Route::get('/leave/balances', [LeaveController::class, 'balances']);
        Route::put('/leave/balances', [LeaveController::class, 'upsertBalance']);
        Route::get('/leave/requests', [LeaveController::class, 'requests']);
        Route::post('/leave/requests', [LeaveController::class, 'submit']);
        Route::get('/leave/requests/{id}', [LeaveController::class, 'show']);
        Route::post('/leave/requests/{id}/cancel', [LeaveController::class, 'cancel']);
        Route::get('/leave/requests/{id}/attachment', [LeaveController::class, 'attachment']);
        Route::post('/leave/requests/{id}/approve', [LeaveController::class, 'approve']);
        Route::post('/leave/requests/{id}/reject', [LeaveController::class, 'reject']);

        // Dashboard
        Route::get('/dashboard/employee', [DashboardController::class, 'employee']);
        Route::get('/dashboard/team', [DashboardController::class, 'team']);
        Route::get('/dashboard/hr', [DashboardController::class, 'hr']);

        // HR Administration: Employees
        Route::get('/employees', [EmployeeController::class, 'index']);
        Route::post('/employees', [EmployeeController::class, 'store']);
        Route::get('/employees/{id}', [EmployeeController::class, 'show']);
        Route::patch('/employees/{id}', [EmployeeController::class, 'update']);
        Route::post('/employees/{id}/deactivate', [EmployeeController::class, 'deactivate']);
        Route::get('/employees/{id}/face-reference', [EmployeeController::class, 'getFaceReference']);
        Route::put('/employees/{id}/face-reference', [EmployeeController::class, 'updateFaceReference']);

        // Master Data
        Route::get('/departments', [MasterDataController::class, 'departments']);
        Route::post('/departments', [MasterDataController::class, 'createDepartment']);
        Route::patch('/departments/{id}', [MasterDataController::class, 'updateDepartment']);

        Route::get('/positions', [MasterDataController::class, 'positions']);
        Route::post('/positions', [MasterDataController::class, 'createPosition']);
        Route::patch('/positions/{id}', [MasterDataController::class, 'updatePosition']);

        Route::get('/shifts', [MasterDataController::class, 'shifts']);
        Route::post('/shifts', [MasterDataController::class, 'createShift']);
        Route::patch('/shifts/{id}', [MasterDataController::class, 'updateShift']);

        Route::get('/work-locations', [MasterDataController::class, 'workLocations']);
        Route::post('/work-locations', [MasterDataController::class, 'createWorkLocation']);
        Route::patch('/work-locations/{id}', [MasterDataController::class, 'updateWorkLocation']);

        Route::get('/reports/attendance-monthly', [MasterDataController::class, 'monthlyReport']);

        // Super Admin
        Route::get('/accounts', [AccountController::class, 'index']);
        Route::post('/accounts', [AccountController::class, 'store']);
        Route::patch('/accounts/{id}', [AccountController::class, 'update']);

        // Settings
        Route::get('/settings', [SettingsController::class, 'show']);
        Route::put('/settings', [SettingsController::class, 'update']);
    });
});
