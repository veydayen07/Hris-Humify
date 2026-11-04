<?php

namespace Database\Seeders;

use App\Enums\AttendanceStatus;
use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\LeaveAttendanceStatus;
use App\Enums\LeaveRequestStatus;
use App\Enums\ReviewStatus;
use App\Enums\ShiftCategory;
use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Position;
use App\Models\Setting;
use App\Models\User;
use App\Models\WorkLocation;
use App\Models\WorkShift;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $password = Hash::make(config('humify.demo_password', 'Password123!'));

        // 1. Settings
        Setting::setValue('company_timezone', config('humify.company_timezone', 'Asia/Jakarta'));
        Setting::setValue('face_match_threshold', config('humify.face_match_threshold', 80));
        Setting::setValue('close_day_time', config('humify.close_day_time', '01:00'));

        // 2. Departments
        $deptProductDesign = Department::query()->create(['name' => 'Product Design', 'description' => 'Product design department']);
        $deptEngineering = Department::query()->create(['name' => 'Engineering', 'description' => 'Engineering department']);
        $deptOperations = Department::query()->create(['name' => 'Operations', 'description' => 'Operations department']);
        $deptFinance = Department::query()->create(['name' => 'Finance', 'description' => 'Finance department']);

        // 3. Positions
        $posProductDesigner = Position::query()->create(['department_id' => $deptProductDesign->id, 'name' => 'Product Designer', 'level' => 2]);
        $posDesignManager = Position::query()->create(['department_id' => $deptProductDesign->id, 'name' => 'Design Manager', 'level' => 5]);
        $posHrManager = Position::query()->create(['department_id' => $deptOperations->id, 'name' => 'HR Manager', 'level' => 5]);
        $posSystemAdmin = Position::query()->create(['department_id' => $deptEngineering->id, 'name' => 'System Administrator', 'level' => 5]);

        // 4. Work Shifts
        $shiftRegular = WorkShift::query()->create([
            'name' => 'Regular',
            'start_time' => null,
            'end_time' => null,
            'category' => ShiftCategory::Regular,
            'is_overnight' => false,
            'is_active' => true,
        ]);
        WorkShift::query()->create([
            'name' => 'Shift 1',
            'start_time' => null,
            'end_time' => null,
            'category' => ShiftCategory::Shifting,
            'is_overnight' => false,
            'is_active' => true,
        ]);
        WorkShift::query()->create([
            'name' => 'Shift 2',
            'start_time' => null,
            'end_time' => null,
            'category' => ShiftCategory::Shifting,
            'is_overnight' => false,
            'is_active' => true,
        ]);
        WorkShift::query()->create([
            'name' => 'Shift 3',
            'start_time' => null,
            'end_time' => null,
            'category' => ShiftCategory::Shifting,
            'is_overnight' => true,
            'is_active' => true,
        ]);

        // 5. Work Location
        $location = WorkLocation::query()->create([
            'name' => config('humify.work_location.name', 'Demo Office'),
            'latitude' => config('humify.work_location.latitude', -6.200000),
            'longitude' => config('humify.work_location.longitude', 106.816666),
            'radius_meters' => config('humify.work_location.radius_meters', 100),
            'is_active' => true,
        ]);

        // 6. Leave Types
        $leaveAnnual = LeaveType::query()->create([
            'name' => 'Annual Leave',
            'is_paid' => true,
            'max_days_per_year' => 12,
            'requires_attachment' => false,
            'tracks_balance' => true,
            'attendance_status' => LeaveAttendanceStatus::OnLeave,
            'is_active' => true,
        ]);

        $leaveSick = LeaveType::query()->create([
            'name' => 'Sick Leave',
            'is_paid' => true,
            'max_days_per_year' => null,
            'requires_attachment' => true,
            'tracks_balance' => false,
            'attendance_status' => LeaveAttendanceStatus::SickLeave,
            'is_active' => true,
        ]);

        $leaveAuthorized = LeaveType::query()->create([
            'name' => 'Authorized Absence',
            'is_paid' => true,
            'max_days_per_year' => null,
            'requires_attachment' => false,
            'tracks_balance' => false,
            'attendance_status' => LeaveAttendanceStatus::AuthorizedAbsence,
            'is_active' => true,
        ]);

        // 7. Employees and Accounts
        // Manager: Maya Chen
        $empMaya = Employee::query()->create([
            'id' => (string) Str::uuid(),
            'employee_code' => 'EMP-002',
            'full_name' => 'Maya Chen',
            'email' => 'maya.chen@humify.com',
            'department_id' => $deptProductDesign->id,
            'position_id' => $posDesignManager->id,
            'employment_type' => EmploymentType::Permanent,
            'employee_status' => EmployeeStatus::Active,
            'is_shift_worker' => false,
            'hire_date' => '2024-01-15',
        ]);
        $userMaya = User::query()->create([
            'id' => (string) Str::uuid(),
            'employee_id' => $empMaya->id,
            'username' => 'maya.chen',
            'password' => $password,
            'role' => UserRole::Manager,
            'is_active' => true,
        ]);

        // Employee: Alex Morgan (subordinate of Maya)
        $empAlex = Employee::query()->create([
            'id' => (string) Str::uuid(),
            'employee_code' => 'EMP-001',
            'full_name' => 'Alex Morgan',
            'email' => 'alex.morgan@humify.com',
            'personal_email' => 'alex.morgan@example.com',
            'phone' => '+1 202 555 0142',
            'address' => '24 Willow Lane, Austin, TX 78701',
            'emergency_contact_name' => 'Taylor Morgan',
            'emergency_contact_phone' => '+1 202 555 0178',
            'emergency_contact_relationship' => 'Partner',
            'department_id' => $deptProductDesign->id,
            'position_id' => $posProductDesigner->id,
            'supervisor_id' => $empMaya->id,
            'employment_type' => EmploymentType::Permanent,
            'employee_status' => EmployeeStatus::Active,
            'is_shift_worker' => false,
            'hire_date' => '2025-03-01',
            'face_reference_photo_url' => 'employees/demo/alex_face.jpg',
            'face_registered_at' => now(),
            'face_registered_by' => null,
        ]);
        $userAlex = User::query()->create([
            'id' => (string) Str::uuid(),
            'employee_id' => $empAlex->id,
            'username' => 'alex.morgan',
            'password' => $password,
            'role' => UserRole::Employee,
            'is_active' => true,
        ]);
        $empAlex->update(['face_registered_by' => $userAlex->id]);

        // HR Admin: Sofia Patel
        $empSofia = Employee::query()->create([
            'id' => (string) Str::uuid(),
            'employee_code' => 'EMP-003',
            'full_name' => 'Sofia Patel',
            'email' => 'sofia.patel@humify.com',
            'department_id' => $deptOperations->id,
            'position_id' => $posHrManager->id,
            'employment_type' => EmploymentType::Permanent,
            'employee_status' => EmployeeStatus::Active,
            'is_shift_worker' => false,
            'hire_date' => '2023-11-01',
        ]);
        $userSofia = User::query()->create([
            'id' => (string) Str::uuid(),
            'employee_id' => $empSofia->id,
            'username' => 'sofia.patel',
            'password' => $password,
            'role' => UserRole::HrAdmin,
            'is_active' => true,
        ]);

        // Super Admin: Daniel Brooks
        $empDaniel = Employee::query()->create([
            'id' => (string) Str::uuid(),
            'employee_code' => 'EMP-004',
            'full_name' => 'Daniel Brooks',
            'email' => 'daniel.brooks@humify.com',
            'department_id' => $deptEngineering->id,
            'position_id' => $posSystemAdmin->id,
            'employment_type' => EmploymentType::Permanent,
            'employee_status' => EmployeeStatus::Active,
            'is_shift_worker' => false,
            'hire_date' => '2023-01-01',
        ]);
        $userDaniel = User::query()->create([
            'id' => (string) Str::uuid(),
            'employee_id' => $empDaniel->id,
            'username' => 'daniel.brooks',
            'password' => $password,
            'role' => UserRole::SuperAdmin,
            'is_active' => true,
        ]);

        // Additional subordinate in Product Design for Maya
        $empSarah = Employee::query()->create([
            'id' => (string) Str::uuid(),
            'employee_code' => 'EMP-005',
            'full_name' => 'Sarah Connor',
            'email' => 'sarah.connor@humify.com',
            'department_id' => $deptProductDesign->id,
            'position_id' => $posProductDesigner->id,
            'supervisor_id' => $empMaya->id,
            'employment_type' => EmploymentType::Permanent,
            'employee_status' => EmployeeStatus::Active,
            'is_shift_worker' => false,
            'hire_date' => '2025-05-10',
        ]);
        User::query()->create([
            'id' => (string) Str::uuid(),
            'employee_id' => $empSarah->id,
            'username' => 'sarah.connor',
            'password' => $password,
            'role' => UserRole::Employee,
            'is_active' => true,
        ]);

        // 8. Leave Balances for 2026
        $currentYear = (int) now()->year;
        LeaveBalance::query()->create([
            'employee_id' => $empAlex->id,
            'leave_type_id' => $leaveAnnual->id,
            'year' => $currentYear,
            'total_days' => 12,
            'used_days' => 4,
        ]);
        LeaveBalance::query()->create([
            'employee_id' => $empMaya->id,
            'leave_type_id' => $leaveAnnual->id,
            'year' => $currentYear,
            'total_days' => 12,
            'used_days' => 2,
        ]);

        // 9. Sample Attendance Records for Alex
        $tz = config('humify.company_timezone', 'Asia/Jakarta');
        $today = CarbonImmutable::now($tz);

        Attendance::query()->create([
            'employee_id' => $empAlex->id,
            'shift_id' => $shiftRegular->id,
            'attendance_date' => $today->subDays(1)->toDateString(),
            'check_in_time' => $today->subDays(1)->setTime(8, 55)->utc(),
            'check_in_lat' => (float) $location->latitude,
            'check_in_lng' => (float) $location->longitude,
            'check_in_face_verified' => true,
            'check_in_face_score' => 95.0,
            'check_in_work_location_id' => $location->id,
            'check_in_geofence_valid' => true,
            'check_in_distance_m' => 12,
            'check_out_time' => $today->subDays(1)->setTime(18, 1)->utc(),
            'check_out_lat' => (float) $location->latitude,
            'check_out_lng' => (float) $location->longitude,
            'check_out_face_verified' => true,
            'check_out_face_score' => 96.0,
            'check_out_work_location_id' => $location->id,
            'check_out_geofence_valid' => true,
            'check_out_distance_m' => 15,
            'status' => AttendanceStatus::Present,
            'review_status' => ReviewStatus::Approved,
            'reviewed_by' => $userMaya->id,
            'reviewed_at' => now(),
            'review_note' => 'Approved by manager',
        ]);

        Attendance::query()->create([
            'employee_id' => $empAlex->id,
            'shift_id' => $shiftRegular->id,
            'attendance_date' => $today->toDateString(),
            'check_in_time' => $today->setTime(8, 57)->utc(),
            'check_in_lat' => (float) $location->latitude,
            'check_in_lng' => (float) $location->longitude,
            'check_in_face_verified' => true,
            'check_in_face_score' => 94.0,
            'check_in_work_location_id' => $location->id,
            'check_in_geofence_valid' => true,
            'check_in_distance_m' => 10,
            'status' => AttendanceStatus::Present,
            'review_status' => ReviewStatus::Pending,
        ]);

        // 10. Sample Leave Requests for Alex
        LeaveRequest::query()->create([
            'employee_id' => $empAlex->id,
            'leave_type_id' => $leaveAnnual->id,
            'start_date' => $today->addDays(5)->toDateString(),
            'end_date' => $today->addDays(6)->toDateString(),
            'total_days' => 2,
            'reason' => 'Family event',
            'status' => LeaveRequestStatus::Pending,
        ]);
        LeaveRequest::query()->create([
            'employee_id' => $empAlex->id,
            'leave_type_id' => $leaveAnnual->id,
            'start_date' => $today->subDays(10)->toDateString(),
            'end_date' => $today->subDays(9)->toDateString(),
            'total_days' => 2,
            'reason' => 'Vacation',
            'status' => LeaveRequestStatus::Approved,
            'approved_by' => $userMaya->id,
            'approved_at' => now()->subDays(11),
        ]);
    }
}
