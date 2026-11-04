<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_attempts', function (Blueprint $table) {
            $table->id();
            $table->uuid('employee_id');
            $table->string('type', 20);
            $table->string('outcome', 20);
            $table->string('failure_code', 50)->nullable();
            $table->decimal('face_score', 5, 2)->nullable();
            $table->unsignedInteger('distance_m')->nullable();
            $table->foreignId('work_location_id')->nullable()->constrained('work_locations')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->index(['employee_id', 'created_at']);
        });

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE attendance_attempts ADD CONSTRAINT chk_attempts_type CHECK (type IN ('check_in', 'check_out'))");
            DB::statement("ALTER TABLE attendance_attempts ADD CONSTRAINT chk_attempts_outcome CHECK (outcome IN ('success', 'failed'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_attempts');
    }
};
