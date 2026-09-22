<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('consultation_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('practitioner_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount_kobo');
            $table->string('currency', 8)->default('NGN');
            $table->timestamps();
        });

        DB::statement('CREATE UNIQUE INDEX fees_hosp_dept_base_unique ON consultation_fees (hospital_id, department_id) WHERE practitioner_id IS NULL');
        DB::statement('CREATE UNIQUE INDEX fees_hosp_dept_pract_unique ON consultation_fees (hospital_id, department_id, practitioner_id) WHERE practitioner_id IS NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultation_fees');
    }
};
