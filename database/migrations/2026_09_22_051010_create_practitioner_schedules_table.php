<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('practitioner_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->constrained()->cascadeOnDelete();
            $table->foreignId('practitioner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedInteger('slot_duration_minutes');
            $table->unsignedInteger('max_per_slot')->default(1);
            $table->time('break_start')->nullable();
            $table->time('break_end')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['practitioner_id', 'department_id', 'weekday', 'start_time'], 'sched_pract_dept_day_start_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('practitioner_schedules');
    }
};
