<?php

namespace Tests\Feature;

use App\Models\Hospital;
use App\Models\Practitioner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_staff(): void
    {
        $hospital = Hospital::create(['name' => 'Test Hospital']);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'hospital_id' => $hospital->id]);
        $staff = User::factory()->create(['role' => User::ROLE_RECEPTIONIST, 'hospital_id' => $hospital->id]);

        $response = $this->actingAs($admin)->get('/admin/staff');

        $response->assertStatus(200);
        $response->assertSee($staff->name);
    }

    public function test_admin_can_create_staff(): void
    {
        $hospital = Hospital::create(['name' => 'Test Hospital']);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'hospital_id' => $hospital->id]);

        $practitioner = Practitioner::create(['hospital_id' => $hospital->id, 'full_name' => 'Dr Test', 'specialisation' => 'General', 'user_id' => null]);

        $response = $this->actingAs($admin)->post('/admin/staff', [
            'name' => 'New Staff',
            'email' => 'staff@example.com',
            'password' => 'password123',
            'role' => User::ROLE_PRACTITIONER,
            'practitioner_id' => $practitioner->id,
        ]);

        $response->assertRedirect('/admin/staff');
        $this->assertDatabaseHas('users', [
            'email' => 'staff@example.com',
            'role' => User::ROLE_PRACTITIONER,
        ]);

        $user = User::where('email', 'staff@example.com')->first();
        $this->assertDatabaseHas('staff', [
            'user_id' => $user->id,
            'hospital_id' => $hospital->id,
        ]);
        $this->assertDatabaseHas('practitioners', [
            'id' => $practitioner->id,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'staff_created',
        ]);
    }

    public function test_admin_can_edit_staff(): void
    {
        $hospital = Hospital::create(['name' => 'Test Hospital']);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'hospital_id' => $hospital->id]);
        $staff = User::factory()->create(['role' => User::ROLE_RECEPTIONIST, 'hospital_id' => $hospital->id, 'name' => 'Old Name']);

        $response = $this->actingAs($admin)->put('/admin/staff/'.$staff->id, [
            'name' => 'New Name',
            'email' => $staff->email,
            'role' => User::ROLE_RECEPTIONIST,
        ]);

        $response->assertRedirect('/admin/staff');
        $this->assertDatabaseHas('users', [
            'id' => $staff->id,
            'name' => 'New Name',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'staff_updated',
        ]);
    }

    public function test_admin_can_toggle_staff_active_status(): void
    {
        $hospital = Hospital::create(['name' => 'Test Hospital']);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'hospital_id' => $hospital->id]);
        $staff = User::factory()->create(['role' => User::ROLE_RECEPTIONIST, 'hospital_id' => $hospital->id, 'is_active' => true]);

        $response = $this->actingAs($admin)->patch('/admin/staff/'.$staff->id.'/toggle');

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $staff->id,
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'staff_status_toggled',
        ]);
    }
}
