<?php

namespace Tests\Feature;

use App\Jobs\ProcessRefund;
use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\Department;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Practitioner;
use App\Models\User;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AppointmentHttpTest extends TestCase
{
    use RefreshDatabase;

    private Hospital $hospital;

    private Department $department;

    private Practitioner $practitioner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hospital = Hospital::create(['name' => 'Test Hospital']);
        $this->department = Department::create([
            'hospital_id' => $this->hospital->id,
            'name' => 'Cardiology',
            'queue_prefix' => 'C',
            'payment_mode' => Department::PAYMENT_ALLOW_BOTH,
            'base_fee_kobo' => 500000,
        ]);
        $this->practitioner = Practitioner::create([
            'hospital_id' => $this->hospital->id,
            'full_name' => 'Dr Ade',
            'specialisation' => 'Cardiology',
        ]);
        $this->practitioner->departments()->attach($this->department->id);
    }

    private function patientUser(string $phone = '+2348000000001'): User
    {
        $user = User::factory()->create([
            'hospital_id' => $this->hospital->id,
            'role' => User::ROLE_PATIENT,
        ]);
        Patient::create([
            'hospital_id' => $this->hospital->id,
            'user_id' => $user->id,
            'full_name' => 'Test Patient',
            'phone' => $phone,
        ]);

        return $user;
    }

    private function slot(?Practitioner $practitioner = null, int $daysAhead = 2, int $hour = 10): AppointmentSlot
    {
        $starts = Carbon::now()->addDays($daysAhead)->setTime($hour, 0);

        return AppointmentSlot::create([
            'hospital_id' => $this->hospital->id,
            'practitioner_id' => ($practitioner ?? $this->practitioner)->id,
            'department_id' => $this->department->id,
            'date' => $starts->toDateString(),
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addMinutes(30),
            'capacity' => 1,
        ]);
    }

    public function test_patient_books_through_http(): void
    {
        $user = $this->patientUser();
        $slot = $this->slot();

        $response = $this->actingAs($user)->post('/patient/book', [
            'department_id' => $this->department->id,
            'practitioner_id' => $this->practitioner->id,
            'slot_id' => $slot->id,
            'payment_mode' => Appointment::PAY_MODE_PHYSICAL,
        ]);

        $appointment = Appointment::firstOrFail();
        $response->assertRedirect("/patient/appointments/{$appointment->id}");
        $this->assertSame(1, $slot->fresh()->booked_count);
    }

    public function test_patient_cannot_book_slot_from_another_department(): void
    {
        $user = $this->patientUser();
        $other = Department::create([
            'hospital_id' => $this->hospital->id,
            'name' => 'Dental',
            'queue_prefix' => 'T',
            'payment_mode' => Department::PAYMENT_ALLOW_BOTH,
            'base_fee_kobo' => 0,
        ]);
        $slot = $this->slot();

        $this->actingAs($user)->post('/patient/book', [
            'department_id' => $other->id,
            'slot_id' => $slot->id,
            'payment_mode' => Appointment::PAY_MODE_PHYSICAL,
        ])->assertSessionHasErrors('slot');
    }

    public function test_patient_cancels_own_appointment_with_reason(): void
    {
        Queue::fake();
        $user = $this->patientUser();

        $appointment = app(BookingService::class)->book(
            $user->patient, $this->department, $this->practitioner, $this->slot(), Appointment::PAY_MODE_PHYSICAL
        );

        $this->actingAs($user)->post("/patient/appointments/{$appointment->id}/cancel", ['reason' => 'Feeling better'])
            ->assertRedirect('/patient/appointments');

        $this->assertSame(Appointment::STATUS_CANCELLED, $appointment->fresh()->status);
        $this->assertSame('Feeling better', $appointment->fresh()->cancellation_reason);
    }

    public function test_patient_cannot_touch_another_patients_appointment(): void
    {
        $user = $this->patientUser('+2348000000001');
        $other = $this->patientUser('+2348000000002');

        $appointment = app(BookingService::class)->book(
            $other->patient, $this->department, $this->practitioner, $this->slot(), Appointment::PAY_MODE_PHYSICAL
        );

        $this->actingAs($user)->get("/patient/appointments/{$appointment->id}")->assertForbidden();
        $this->actingAs($user)->post("/patient/appointments/{$appointment->id}/cancel")->assertForbidden();
    }

    public function test_patient_reschedules_through_http(): void
    {
        $user = $this->patientUser();

        $appointment = app(BookingService::class)->book(
            $user->patient, $this->department, $this->practitioner, $this->slot(), Appointment::PAY_MODE_PHYSICAL
        );
        $newSlot = $this->slot(daysAhead: 3, hour: 11);

        $this->actingAs($user)->post("/patient/appointments/{$appointment->id}/reschedule", ['slot_id' => $newSlot->id])
            ->assertRedirect("/patient/appointments/{$appointment->id}");

        $this->assertSame($newSlot->id, $appointment->fresh()->slot_id);
        $this->assertSame(1, $appointment->fresh()->reschedule_count);
    }

    public function test_receptionist_books_on_behalf(): void
    {
        $receptionist = User::factory()->create([
            'hospital_id' => $this->hospital->id,
            'role' => User::ROLE_RECEPTIONIST,
        ]);
        $patient = Patient::create([
            'hospital_id' => $this->hospital->id,
            'full_name' => 'Desk Patient',
            'phone' => '+2348000000009',
        ]);
        $slot = $this->slot();

        $this->actingAs($receptionist)->post('/staff/appointments', [
            'patient_id' => $patient->id,
            'department_id' => $this->department->id,
            'practitioner_id' => $this->practitioner->id,
            'slot_id' => $slot->id,
            'payment_mode' => Appointment::PAY_MODE_PHYSICAL,
        ])->assertRedirect('/staff/appointments');

        $this->assertDatabaseHas('appointments', ['patient_id' => $patient->id]);
    }

    public function test_receptionist_marks_no_show(): void
    {
        $receptionist = User::factory()->create([
            'hospital_id' => $this->hospital->id,
            'role' => User::ROLE_RECEPTIONIST,
        ]);
        $patient = Patient::create([
            'hospital_id' => $this->hospital->id,
            'full_name' => 'NoShow Patient',
            'phone' => '+2348000000008',
        ]);

        $appointment = app(BookingService::class)->book(
            $patient, $this->department, $this->practitioner, $this->slot(), Appointment::PAY_MODE_PHYSICAL
        );

        $this->actingAs($receptionist)->post("/staff/appointments/{$appointment->id}/no-show")
            ->assertRedirect();

        $this->assertSame(Appointment::STATUS_NO_SHOW, $appointment->fresh()->status);
    }

    public function test_admin_bulk_moves_to_replacement_and_cancels_rest(): void
    {
        Queue::fake();
        $admin = User::factory()->create([
            'hospital_id' => $this->hospital->id,
            'role' => User::ROLE_ADMIN,
        ]);

        $replacement = Practitioner::create([
            'hospital_id' => $this->hospital->id,
            'full_name' => 'Dr Bola',
            'specialisation' => 'Cardiology',
        ]);
        $replacement->departments()->attach($this->department->id);

        $day = Carbon::now()->addDays(4)->startOfDay();
        $bookings = app(BookingService::class);

        $patientA = Patient::create(['hospital_id' => $this->hospital->id, 'full_name' => 'A', 'phone' => '+2348000000011']);
        $patientB = Patient::create(['hospital_id' => $this->hospital->id, 'full_name' => 'B', 'phone' => '+2348000000012']);

        $slotA = $this->slot(daysAhead: 4, hour: 9);
        $slotB = $this->slot(daysAhead: 4, hour: 10);
        $apptA = $bookings->book($patientA, $this->department, $this->practitioner, $slotA, Appointment::PAY_MODE_PHYSICAL);
        $apptB = $bookings->book($patientB, $this->department, $this->practitioner, $slotB, Appointment::PAY_MODE_ONLINE);
        $apptB->update(['payment_status' => Appointment::PAY_PAID]);

        // Replacement holds a single same-day slot: only one booking can move.
        $repStarts = $day->copy()->setTime(9, 0);
        AppointmentSlot::create([
            'hospital_id' => $this->hospital->id,
            'practitioner_id' => $replacement->id,
            'department_id' => $this->department->id,
            'date' => $day->toDateString(),
            'starts_at' => $repStarts,
            'ends_at' => $repStarts->copy()->addMinutes(30),
            'capacity' => 1,
        ]);

        // Preview first.
        $preview = $this->actingAs($admin)->get(
            "/admin/bulk-cancellation?practitioner_id={$this->practitioner->id}&date={$day->toDateString()}"
        );
        $preview->assertOk();

        $this->actingAs($admin)->post('/admin/bulk-cancellation', [
            'practitioner_id' => $this->practitioner->id,
            'date' => $day->toDateString(),
            'reason' => 'Sick day',
            'replacement_practitioner_id' => $replacement->id,
        ])->assertRedirect('/admin/bulk-cancellation');

        $this->assertSame($replacement->id, $apptA->fresh()->practitioner_id);
        $this->assertSame(Appointment::STATUS_SCHEDULED, $apptA->fresh()->status);
        $this->assertSame(Appointment::STATUS_CANCELLED, $apptB->fresh()->status);
        $this->assertSame('Sick day', $apptB->fresh()->cancellation_reason);
        Queue::assertPushed(ProcessRefund::class);
    }
}
