<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Practitioner;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagementTest extends TestCase
{
    use RefreshDatabase;

    private function hospital(string $name = 'Test Hospital'): Hospital
    {
        return Hospital::create(['name' => $name]);
    }

    private function user(Hospital $hospital, string $role): User
    {
        return User::factory()->create([
            'hospital_id' => $hospital->id,
            'role' => $role,
        ]);
    }

    private function patientUser(Hospital $hospital): User
    {
        $user = $this->user($hospital, User::ROLE_PATIENT);
        Patient::create([
            'hospital_id' => $hospital->id,
            'user_id' => $user->id,
            'full_name' => 'Old Name',
            'phone' => '+2348000000001',
        ]);

        return $user;
    }

    public function test_patient_updates_own_profile(): void
    {
        $user = $this->patientUser($this->hospital());

        $response = $this->actingAs($user)->patch('/patient/profile', [
            'full_name' => 'New Name',
            'phone' => '+2348000000001',
            'date_of_birth' => '1990-01-01',
            'gender' => 'Female',
            'address' => '1 New Street',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');
        $this->assertSame('New Name', $user->fresh()->name);
        $this->assertSame('1 New Street', $user->patient()->first()->address);
    }

    public function test_receptionist_registers_patient(): void
    {
        $hospital = $this->hospital();
        $receptionist = $this->user($hospital, User::ROLE_RECEPTIONIST);

        $response = $this->actingAs($receptionist)->post('/staff/patients', [
            'full_name' => 'Desk Patient',
            'phone' => '+2348000000002',
            'email' => 'desk@example.com',
            'date_of_birth' => '1985-03-03',
            'gender' => 'Male',
            'address' => '2 Desk Road',
        ]);

        $response->assertRedirect('/staff/patients');
        $this->assertDatabaseHas('patients', [
            'hospital_id' => $hospital->id,
            'phone' => '+2348000000002',
        ]);
    }

    public function test_duplicate_phone_rejected_within_hospital_but_allowed_across(): void
    {
        $hospitalA = $this->hospital('Hospital A');
        $hospitalB = $this->hospital('Hospital B');
        $receptionist = $this->user($hospitalA, User::ROLE_RECEPTIONIST);

        Patient::create(['hospital_id' => $hospitalA->id, 'full_name' => 'Existing', 'phone' => '+2348000000003']);

        $payload = [
            'full_name' => 'Duplicate',
            'phone' => '+2348000000003',
            'email' => 'dup@example.com',
            'date_of_birth' => '1990-01-01',
            'gender' => 'Female',
            'address' => '3 Dup Lane',
        ];

        $this->actingAs($receptionist)->post('/staff/patients', $payload)
            ->assertSessionHasErrors('phone');

        $receptionistB = $this->user($hospitalB, User::ROLE_RECEPTIONIST);
        $this->actingAs($receptionistB)->post('/staff/patients', $payload)
            ->assertRedirect('/staff/patients');
    }

    public function test_practitioner_cannot_register_patients(): void
    {
        $practitioner = $this->user($this->hospital(), User::ROLE_PRACTITIONER);

        $this->actingAs($practitioner)->get('/staff/patients/create')->assertForbidden();
    }

    public function test_practitioner_can_access_search_and_patient_directory(): void
    {
        $hospital = $this->hospital();
        $practitioner = $this->user($hospital, User::ROLE_PRACTITIONER);

        $this->actingAs($practitioner)->get('/staff/search')->assertOk();
        $this->actingAs($practitioner)->get('/staff/patients')->assertOk();
    }

    public function test_admin_manages_departments(): void
    {
        $hospital = $this->hospital();
        $admin = $this->user($hospital, User::ROLE_ADMIN);

        $this->actingAs($admin)->post('/admin/departments', [
            'name' => 'Oncology',
            'queue_prefix' => 'O',
            'payment_mode' => Department::PAYMENT_ALLOW_BOTH,
            'base_fee_kobo' => 750000,
        ])->assertRedirect('/admin/departments');

        $department = Department::where('hospital_id', $hospital->id)->where('name', 'Oncology')->firstOrFail();

        $this->actingAs($admin)->put("/admin/departments/{$department->id}", [
            'name' => 'Oncology',
            'queue_prefix' => 'O',
            'payment_mode' => Department::PAYMENT_PHYSICAL_ONLY,
            'base_fee_kobo' => 750000,
            'is_active' => false,
        ])->assertRedirect('/admin/departments');

        $this->assertFalse($department->fresh()->is_active);
    }

    public function test_department_delete_blocked_with_linked_practitioner(): void
    {
        $hospital = $this->hospital();
        $admin = $this->user($hospital, User::ROLE_ADMIN);
        $department = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'Linked Dept',
            'queue_prefix' => 'L',
            'payment_mode' => Department::PAYMENT_ALLOW_BOTH,
            'base_fee_kobo' => 0,
        ]);
        $practitioner = Practitioner::create([
            'hospital_id' => $hospital->id,
            'full_name' => 'Dr Linked',
            'specialisation' => 'General',
        ]);
        $practitioner->departments()->attach($department->id);

        $this->actingAs($admin)->delete("/admin/departments/{$department->id}")
            ->assertSessionHasErrors('department');

        $this->assertDatabaseHas('departments', ['id' => $department->id]);
    }

    public function test_receptionist_cannot_manage_departments(): void
    {
        $receptionist = $this->user($this->hospital(), User::ROLE_RECEPTIONIST);

        $this->actingAs($receptionist)->get('/admin/departments')->assertForbidden();
    }

    public function test_admin_updates_settings_and_session_uses_them(): void
    {
        $hospital = $this->hospital();
        $admin = $this->user($hospital, User::ROLE_ADMIN);

        $this->actingAs($admin)->put('/admin/settings', [
            'session_lifetime_staff' => 60,
            'session_lifetime_patient' => 120,
            'queue_low_threshold' => 3,
            'booking_window_days' => 30,
            'booking_cutoff_minutes' => 120,
        ])->assertRedirect();

        $this->assertSame('60', Setting::get($hospital->id, Setting::SESSION_LIFETIME_STAFF));

        $patient = $this->patientUser($hospital);
        $this->actingAs($patient)->get('/patient/dashboard')->assertOk();
        $this->assertSame(120, config('session.lifetime'));
    }
}
