<?php

namespace Tests\Feature;

use App\Models\Hospital;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientFileTest extends TestCase
{
    use RefreshDatabase;

    private Hospital $hospital;

    private User $receptionist;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hospital = Hospital::create(['name' => 'Test Hospital', 'is_active' => true]);

        $this->receptionist = User::create([
            'hospital_id' => $this->hospital->id,
            'name' => 'Receptionist',
            'email' => 'rec@hospital.com',
            'password' => 'password',
            'role' => User::ROLE_RECEPTIONIST,
            'is_active' => true,
        ]);

        $this->patient = Patient::create([
            'hospital_id' => $this->hospital->id,
            'patient_number' => 'PAT-2026-00001',
            'full_name' => 'Jane Doe',
            'phone' => '08012345678',
            'email' => 'jane@example.com',
            'date_of_birth' => '1990-01-01',
            'gender' => 'female',
            'address' => '123 Health Way',
        ]);
    }

    public function test_receptionist_can_view_patient_file(): void
    {
        $response = $this->actingAs($this->receptionist)
            ->get("/staff/patients/{$this->patient->id}");

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Staff/Patients/Show')
                ->where('patient.patient_number', 'PAT-2026-00001')
                ->where('patient.full_name', 'Jane Doe')
            );
    }

    public function test_receptionist_can_filter_and_search_patients(): void
    {
        Patient::create([
            'hospital_id' => $this->hospital->id,
            'patient_number' => 'PAT-2026-00002',
            'full_name' => 'John Smith',
            'phone' => '08099998888',
            'email' => 'john@example.com',
            'date_of_birth' => '1985-05-05',
            'gender' => 'male',
            'address' => '456 Clinic Road',
        ]);

        $response = $this->actingAs($this->receptionist)
            ->get('/staff/patients?search=Jane&gender=female');

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Staff/Patients/Index')
                ->has('patients.data', 1)
                ->where('patients.data.0.full_name', 'Jane Doe')
                ->where('filters.search', 'Jane')
                ->where('filters.gender', 'female')
            );
    }
}
