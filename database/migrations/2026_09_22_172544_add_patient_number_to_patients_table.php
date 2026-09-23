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
        Schema::table('patients', function (Blueprint $table) {
            $table->string('patient_number')->nullable()->after('user_id');
            $table->unique(['hospital_id', 'patient_number']);
        });

        // Backfill pre-existing patient records without patient numbers
        $patients = DB::table('patients')->whereNull('patient_number')->orderBy('id')->get();
        foreach ($patients as $index => $patient) {
            $sequence = $index + 1;
            $number = sprintf('PAT-%s-%05d', date('Y'), $sequence);
            DB::table('patients')->where('id', $patient->id)->update([
                'patient_number' => $number,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropUnique(['hospital_id', 'patient_number']);
            $table->dropColumn('patient_number');
        });
    }
};
