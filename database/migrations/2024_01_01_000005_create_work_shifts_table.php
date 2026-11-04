<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('category', 20);
            $table->boolean('is_overnight')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE work_shifts ADD CONSTRAINT chk_work_shifts_category CHECK (category IN ('regular', 'shifting'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('work_shifts');
    }
};
