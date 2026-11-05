<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Department;
use App\Models\Hospital;
use App\Models\Patient;
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

    private function departmentNamed(Hospital $hospital, string $name, string $prefix): Department
    {
        return Department::create([
            'hospital_id' => $hospital->id,
            'name' => $name,
            'queue_prefix' => $prefix,
            'payment_mode' => Department::PAYMENT_ALLOW_BOTH,
            'base_fee_kobo' => 0,
        ]);
    }

    private function practitionerWithDepartments(Hospital $hospital, User $user, array $departments): void
    {
        $practitioner = Practitioner::create([
            'hospital_id' => $hospital->id,
            'user_id' => $user->id,
            'full_name' => 'Dr Scoped',
            'specialisation' => 'General',
        ]);
        $practitioner->departments()->attach(collect($departments)->map->id);
    }

    public function test_practitioner_overview_shows_only_own_departments(): void
    {
        $hospital = $this->hospital();
        $ownA = $this->departmentNamed($hospital, 'Cardiology', 'C');
        $ownB = $this->departmentNamed($hospital, 'Dental', 'D');
        $other = $this->departmentNamed($hospital, 'Eye Clinic', 'E');
        $user = $this->user($hospital, User::ROLE_PRACTITIONER);
        $this->practitionerWithDepartments($hospital, $user, [$ownA, $ownB]);

        $response = $this->actingAs($user)->get('/staff/queue')->assertOk();

        $overviewIds = collect($response->viewData('page')['props']['overview'] ?? [])
            ->pluck('id')->sort()->values()->all();

        $this->assertSame(
            collect([$ownA->id, $ownB->id])->sort()->values()->all(),
            $overviewIds
        );
        $this->assertNotContains($other->id, $overviewIds);
    }

    public function test_practitioner_cannot_open_other_department_via_query_param(): void
    {
        $hospital = $this->hospital();
        $own = $this->departmentNamed($hospital, 'Cardiology', 'C');
        $other = $this->departmentNamed($hospital, 'Eye Clinic', 'E');
        $user = $this->user($hospital, User::ROLE_PRACTITIONER);
        $this->practitionerWithDepartments($hospital, $user, [$own]);

        $response = $this->actingAs($user)->get("/staff/queue?department_id={$other->id}")->assertOk();

        $props = $response->viewData('page')['props'];

        $this->assertSame($own->id, $props['department_id']);
    }

    public function test_receptionist_overview_still_shows_all_departments(): void
    {
        $hospital = $this->hospital();
        $this->departmentNamed($hospital, 'Cardiology', 'C');
        $this->departmentNamed($hospital, 'Dental', 'D');
        $receptionist = $this->user($hospital, User::ROLE_RECEPTIONIST);

        $response = $this->actingAs($receptionist)->get('/staff/queue')->assertOk();

        $this->assertCount(2, $response->viewData('page')['props']['overview'] ?? []);
    }

    public function test_staff_dashboard_counts_are_scoped_to_own_departments(): void
    {
        $hospital = $this->hospital();
        $own = $this->departmentNamed($hospital, 'Cardiology', 'C');
        $other = $this->departmentNamed($hospital, 'Eye Clinic', 'E');
        $user = $this->user($hospital, User::ROLE_PRACTITIONER);
        $this->practitionerWithDepartments($hospital, $user, [$own]);

        $today = now()->toDateString();
        foreach ([$own, $other] as $dept) {
            Appointment::create([
                'hospital_id' => $hospital->id,
                'patient_id' => Patient::create([
                    'hospital_id' => $hospital->id,
                    'full_name' => "Patient {$dept->name}",
                    'phone' => '+234800'.random_int(1000000, 9999999),
                ])->id,
                'department_id' => $dept->id,
                'scheduled_at' => "{$today} 09:00:00",
                'status' => Appointment::STATUS_SCHEDULED,
                'payment_mode' => Appointment::PAY_MODE_PHYSICAL,
                'payment_status' => Appointment::PAY_UNPAID,
            ]);
        }

        $response = $this->actingAs($user)->get('/staff/dashboard')->assertOk();
        $counts = $response->viewData('page')['props']['appointments'];

        $this->assertSame(1, $counts['scheduled'] ?? 0);

        $receptionist = $this->user($hospital, User::ROLE_RECEPTIONIST);
        $all = $this->actingAs($receptionist)->get('/staff/dashboard')->assertOk();
        $this->assertSame(2, $all->viewData('page')['props']['appointments']['scheduled'] ?? 0);
    }
}
