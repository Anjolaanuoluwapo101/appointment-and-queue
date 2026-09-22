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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('appointment_id');
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 32);
            $table->string('reference')->unique();
            $table->unsignedInteger('amount_kobo');
            $table->string('status', 32)->default('pending');
            $table->dateTime('paid_at')->nullable();
            $table->boolean('verified_via_webhook')->default(false);
            $table->string('receipt_no')->nullable();
            $table->string('method', 32)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['hospital_id', 'status']);
            $table->index('appointment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
