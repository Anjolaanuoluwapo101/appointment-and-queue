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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('practitioner_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('slot_id')->nullable()->constrained('appointment_slots')->nullOnDelete();
            $table->dateTime('scheduled_at');
            $table->string('status', 32)->default('scheduled');
            $table->string('payment_mode', 32)->default('physical');
            $table->string('payment_status', 32)->default('unpaid');
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->boolean('is_walk_in')->default(false);
            $table->string('cancellation_reason')->nullable();
            $table->unsignedInteger('reschedule_count')->default(0);
            $table->foreignId('checked_in_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('checked_in_at')->nullable();
            $table->timestamps();

            $table->index(['hospital_id', 'department_id', 'scheduled_at']);
            $table->index(['hospital_id', 'patient_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
