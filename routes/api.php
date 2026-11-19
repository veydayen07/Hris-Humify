<?php

use App\Http\Controllers\Api\v1\AttendanceController;
use App\Http\Controllers\Api\v1\AuthController;
use App\Http\Controllers\Api\v1\LeaveController;
use App\Http\Controllers\Api\v1\ManagerController;
use App\Http\Controllers\Api\v1\OfficeLocationController;
use App\Http\Controllers\Api\v1\ProfileController;
use App\Http\Middleware\ApiTokenAuth;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/ping', [AuthController::class, 'ping']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Authenticated routes
Route::middleware([ApiTokenAuth::class])->group(function () {
    // Auth & Profile
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/face-reference', [ProfileController::class, 'registerFace']);

    // Attendance
    Route::get('/attendances/today', [AttendanceController::class, 'today']);
    Route::post('/attendances/check-in', [AttendanceController::class, 'checkIn']);
    Route::post('/attendances/check-out', [AttendanceController::class, 'checkOut']);
    Route::get('/attendances/history', [AttendanceController::class, 'history']);

    // Leaves & Permits
    Route::get('/leaves/types', [LeaveController::class, 'types']);
    Route::get('/leaves/balances', [LeaveController::class, 'balances']);
    Route::get('/leaves/requests', [LeaveController::class, 'requests']);
    Route::post('/leaves/requests', [LeaveController::class, 'submit']);
    Route::post('/leaves/requests/{id}/cancel', [LeaveController::class, 'cancel']);

    // Office Location Settings
    Route::get('/office-location', [OfficeLocationController::class, 'show']);
    Route::middleware([ApiTokenAuth::class . ':admin_hr'])->group(function () {
        Route::post('/office-location', [OfficeLocationController::class, 'update']);
        Route::put('/office-location', [OfficeLocationController::class, 'update']);
    });

    // Manager Workspace (Manager & HR)
    Route::middleware([ApiTokenAuth::class . ':manager'])->prefix('manager')->group(function () {
        Route::get('/attendance-reviews', [ManagerController::class, 'attendanceReviews']);
        Route::post('/attendance-reviews/{id}/decide', [ManagerController::class, 'decideAttendanceReview']);
        Route::get('/leave-approvals', [ManagerController::class, 'leaveApprovals']);
        Route::post('/leave-approvals/{id}/decide', [ManagerController::class, 'decideLeaveApproval']);
    });
});
