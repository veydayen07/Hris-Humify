<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->uuid('employee_id');
            $table->foreignId('shift_id')->nullable()->constrained('work_shifts')->nullOnDelete();
            $table->date('attendance_date');

            $table->timestamp('check_in_time')->nullable();
            $table->double('check_in_lat')->nullable();
            $table->double('check_in_lng')->nullable();
            $table->text('check_in_photo_url')->nullable();
            $table->boolean('check_in_face_verified')->nullable();
            $table->decimal('check_in_face_score', 5, 2)->nullable();
            $table->foreignId('check_in_work_location_id')->nullable()->constrained('work_locations')->nullOnDelete();
            $table->boolean('check_in_geofence_valid')->nullable();
            $table->unsignedInteger('check_in_distance_m')->nullable();

            $table->timestamp('check_out_time')->nullable();
            $table->double('check_out_lat')->nullable();
            $table->double('check_out_lng')->nullable();
            $table->text('check_out_photo_url')->nullable();
            $table->boolean('check_out_face_verified')->nullable();
            $table->decimal('check_out_face_score', 5, 2)->nullable();
            $table->foreignId('check_out_work_location_id')->nullable()->constrained('work_locations')->nullOnDelete();
            $table->boolean('check_out_geofence_valid')->nullable();
            $table->unsignedInteger('check_out_distance_m')->nullable();

            $table->string('status', 30)->default('present');
            $table->unsignedInteger('late_minutes')->default(0);
            $table->unsignedInteger('early_leave_minutes')->default(0);
            $table->unsignedInteger('work_minutes')->nullable();
            $table->text('notes')->nullable();

            $table->string('review_status', 20)->default('pending');
            $table->uuid('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();

            $table->string('idempotency_key')->nullable();

            $table->timestamps();

            $table->unique(['employee_id', 'attendance_date']);
            $table->index(['review_status', 'attendance_date']);
            $table->index('attendance_date');
            $table->index('status');
            $table->index(['employee_id', 'idempotency_key']);

            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
        });

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE attendances ADD CONSTRAINT chk_attendances_status CHECK (status IN ('present', 'late', 'left_early', 'authorized_absence', 'sick_leave', 'on_leave', 'absent', 'day_off'))");
            DB::statement("ALTER TABLE attendances ADD CONSTRAINT chk_attendances_review_status CHECK (review_status IN ('pending', 'approved', 'rejected', 'not_applicable'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
