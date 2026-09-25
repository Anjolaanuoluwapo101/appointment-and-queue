<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Hospital;
use App\Models\Practitioner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueBoardTest extends TestCase
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

    private function department(Hospital $hospital): Department
    {
        return Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'General OPD',
            'queue_prefix' => 'G',
            'payment_mode' => Department::PAYMENT_ALLOW_BOTH,
            'base_fee_kobo' => 0,
        ]);
    }

    public function test_receptionist_opens_queue_board_without_lazy_load_errors(): void
    {
        $hospital = $this->hospital();
        $this->department($hospital);
        $receptionist = $this->user($hospital, User::ROLE_RECEPTIONIST);

        // Runs with preventLazyLoading enforced: any lazy relation access
        // on this page throws instead of silently querying.
        $this->actingAs($receptionist)->get('/staff/queue')->assertOk();
    }

    public function test_practitioner_opens_own_board_without_lazy_load_errors(): void
    {
        $hospital = $this->hospital();
        $department = $this->department($hospital);
        $user = $this->user($hospital, User::ROLE_PRACTITIONER);

        $practitioner = Practitioner::create([
            'hospital_id' => $hospital->id,
            'user_id' => $user->id,
            'full_name' => 'Dr Board',
            'specialisation' => 'General',
        ]);
        $practitioner->departments()->attach($department->id);

        $this->actingAs($user)->get('/staff/my-queue')->assertOk();
    }

    public function test_public_display_board_renders_without_login(): void
    {
        $hospital = $this->hospital();
        $department = $this->department($hospital);

        $this->get("/display/queue/{$department->id}")->assertOk();
    }
}
