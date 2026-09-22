<?php

namespace Tests\Feature;

use App\Models\Hospital;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function hospital(): Hospital
    {
        return Hospital::create(['name' => 'Test Hospital']);
    }

    public function test_patient_can_login_through_patient_portal(): void
    {
        $hospital = $this->hospital();
        $user = User::factory()->create([
            'hospital_id' => $hospital->id,
            'role' => User::ROLE_PATIENT,
        ]);

        $response = $this->post('/login/patient', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/patient/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_staff_can_login_through_staff_portal(): void
    {
        $hospital = $this->hospital();
        $user = User::factory()->create([
            'hospital_id' => $hospital->id,
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this->post('/login/staff', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/staff/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_patient_is_rejected_at_staff_portal(): void
    {
        $hospital = $this->hospital();
        $user = User::factory()->create([
            'hospital_id' => $hospital->id,
            'role' => User::ROLE_PATIENT,
        ]);

        $response = $this->post('/login/staff', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_staff_is_rejected_at_patient_portal(): void
    {
        $hospital = $this->hospital();
        $user = User::factory()->create([
            'hospital_id' => $hospital->id,
            'role' => User::ROLE_RECEPTIONIST,
        ]);

        $response = $this->post('/login/patient', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $hospital = $this->hospital();
        $user = User::factory()->create([
            'hospital_id' => $hospital->id,
            'role' => User::ROLE_PATIENT,
            'is_active' => false,
        ]);

        $response = $this->post('/login/patient', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_guests_cannot_access_dashboards(): void
    {
        $this->get('/patient/dashboard')->assertRedirect('/login/patient');
        $this->get('/staff/dashboard')->assertRedirect('/login/patient');
    }

    public function test_patient_cannot_access_staff_dashboard(): void
    {
        $hospital = $this->hospital();
        $user = User::factory()->create([
            'hospital_id' => $hospital->id,
            'role' => User::ROLE_PATIENT,
        ]);

        $this->actingAs($user)->get('/staff/dashboard')->assertForbidden();
    }

    public function test_patient_registration_creates_user_and_patient(): void
    {
        $hospital = $this->hospital();

        $response = $this->post('/register', [
            'full_name' => 'Ada Test',
            'phone' => '+2348012345678',
            'email' => 'ada@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'date_of_birth' => '1990-05-01',
            'gender' => 'Female',
            'address' => '12 Test Street, Lagos',
        ]);

        $response->assertRedirect('/patient/dashboard');
        $this->assertAuthenticated();

        $user = User::where('email', 'ada@example.com')->firstOrFail();
        $this->assertSame(User::ROLE_PATIENT, $user->role);
        $this->assertSame($hospital->id, $user->hospital_id);

        $patient = Patient::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('+2348012345678', $patient->phone);
    }
}
