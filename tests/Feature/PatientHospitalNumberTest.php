<?php

namespace Tests\Feature;

use App\Models\Hospital;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientHospitalNumberTest extends TestCase
{
    use RefreshDatabase;

    private function createHospital(): Hospital
    {
        return Hospital::create([
            'name' => 'City General Hospital',
            'slug' => 'city-general',
            'is_active' => true,
        ]);
    }

    public function test_patient_auto_generates_hospital_number_if_left_blank(): void
    {
        $hospital = $this->createHospital();

        $patient = Patient::create([
            'hospital_id' => $hospital->id,
            'full_name' => 'John Doe',
            'phone' => '08012345678',
            'email' => 'john@example.com',
        ]);

        $this->assertNotNull($patient->patient_number);
        $this->assertStringStartsWith('PAT-'.date('Y').'-', $patient->patient_number);
    }

    public function test_patient_uses_manual_hospital_number_if_provided(): void
    {
        $hospital = $this->createHospital();

        $patient = Patient::create([
            'hospital_id' => $hospital->id,
            'patient_number' => 'CARD-10099',
            'full_name' => 'Jane Doe',
            'phone' => '08087654321',
            'email' => 'jane@example.com',
        ]);

        $this->assertEquals('CARD-10099', $patient->patient_number);
    }

    public function test_sequential_ticker_increments_for_subsequent_patients(): void
    {
        $hospital = $this->createHospital();

        $p1 = Patient::create([
            'hospital_id' => $hospital->id,
            'full_name' => 'Patient One',
            'phone' => '08011111111',
        ]);

        $p2 = Patient::create([
            'hospital_id' => $hospital->id,
            'full_name' => 'Patient Two',
            'phone' => '08022222222',
        ]);

        $this->assertNotEquals($p1->patient_number, $p2->patient_number);

        $num1 = (int) substr($p1->patient_number, -5);
        $num2 = (int) substr($p2->patient_number, -5);

        $this->assertEquals($num1 + 1, $num2);
    }

    public function test_staff_can_search_patient_by_hospital_number(): void
    {
        $hospital = $this->createHospital();
        $staff = User::factory()->create([
            'hospital_id' => $hospital->id,
            'role' => User::ROLE_RECEPTIONIST,
        ]);

        $patient = Patient::create([
            'hospital_id' => $hospital->id,
            'patient_number' => 'MRN-778899',
            'full_name' => 'Sam Search',
            'phone' => '08099999999',
        ]);

        $response = $this->actingAs($staff)->get('/staff/search?q=MRN-778899');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Staff/Search')
            ->has('patients', 1)
            ->where('patients.0.patient_number', 'MRN-778899')
        );
    }
}
