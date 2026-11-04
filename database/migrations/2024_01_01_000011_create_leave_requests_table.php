<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('employee_id');
            $table->foreignId('leave_type_id')->constrained('leave_types')->restrictOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('total_days', 5, 1);
            $table->text('reason')->nullable();
            $table->text('attachment_url')->nullable();
            $table->string('status', 20)->default('pending');
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->uuid('canceled_by')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('canceled_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['employee_id', 'status']);
            $table->index(['status', 'start_date']);
            $table->index(['start_date', 'end_date']);
        });

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE leave_requests ADD CONSTRAINT chk_leave_requests_dates CHECK (end_date >= start_date)");
            DB::statement("ALTER TABLE leave_requests ADD CONSTRAINT chk_leave_requests_status CHECK (status IN ('pending', 'approved', 'rejected', 'canceled'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
