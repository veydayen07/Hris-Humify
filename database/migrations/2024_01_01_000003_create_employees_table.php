<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('employee_code', 30)->unique();
            $table->string('full_name', 150);
            $table->string('email', 150)->nullable()->unique();
            $table->string('personal_email', 150)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('gender', 1)->nullable();
            $table->string('birth_place', 100)->nullable();
            $table->date('birth_date')->nullable();
            $table->text('address')->nullable();
            $table->text('photo_url')->nullable();

            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone', 20)->nullable();
            $table->string('emergency_contact_relationship', 50)->nullable();

            $table->text('face_reference_photo_url')->nullable();
            $table->timestamp('face_registered_at')->nullable();
            $table->uuid('face_registered_by')->nullable();

            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->uuid('supervisor_id')->nullable();

            $table->string('employment_type', 20)->default('permanent');
            $table->string('employee_status', 20)->default('active');
            $table->boolean('is_shift_worker')->default(false);

            $table->date('hire_date');
            $table->date('resign_date')->nullable();

            $table->timestamps();

            $table->index('department_id');
            $table->index('employee_status');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->foreign('supervisor_id')->references('id')->on('employees')->nullOnDelete();
        });

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE employees ADD CONSTRAINT chk_employees_resign_date CHECK (resign_date IS NULL OR resign_date >= hire_date)");
            DB::statement("ALTER TABLE employees ADD CONSTRAINT chk_employees_employment_type CHECK (employment_type IN ('permanent', 'contract', 'intern', 'daily'))");
            DB::statement("ALTER TABLE employees ADD CONSTRAINT chk_employees_status CHECK (employee_status IN ('active', 'inactive', 'resigned', 'long_leave'))");
            DB::statement("ALTER TABLE employees ADD CONSTRAINT chk_employees_gender CHECK (gender IS NULL OR gender IN ('L', 'P'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
