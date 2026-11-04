<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->boolean('is_paid')->default(true);
            $table->unsignedInteger('max_days_per_year')->nullable();
            $table->boolean('requires_attachment')->default(false);
            $table->boolean('tracks_balance')->default(false);
            $table->string('attendance_status', 30)->default('on_leave');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE leave_types ADD CONSTRAINT chk_leave_types_attendance_status CHECK (attendance_status IN ('on_leave', 'sick_leave', 'authorized_absence'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};
