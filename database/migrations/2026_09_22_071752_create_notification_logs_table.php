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
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->constrained()->cascadeOnDelete();
            $table->foreignId('notification_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel', 32)->default('mail');
            $table->string('recipient');
            $table->string('subject');
            $table->string('status', 32)->default('sent');
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['hospital_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
