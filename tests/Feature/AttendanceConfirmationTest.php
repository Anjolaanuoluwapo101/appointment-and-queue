<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\Department;
use App\Models\Hospital;
use App\Models\Notification;
use App\Models\Patient;
use App\Models\Practitioner;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AttendanceConfirmationTest extends TestCase
{
    use RefreshDatabase;

    private Hospital $hospital;

    private Department $department;

    private Practitioner $practitioner;

    private int $slotCounter = 0;

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

    private function patientUser(string $phone): User
    {
        $user = User::factory()->create([
            'hospital_id' => $this->hospital->id,
            'role' => User::ROLE_PATIENT,
        ]);
        Patient::create([
            'hospital_id' => $this->hospital->id,
            'user_id' => $user->id,
            'full_name' => "Patient {$phone}",
            'phone' => $phone,
            'email' => $user->email,
        ]);

        return $user;
    }

    private function scheduledAppointment(User $user, ?Carbon $at = null): Appointment
    {
        $starts = ($at ?? Carbon::now()->addDay())->copy()->setTime(10, 0)->addMinutes(30 * $this->slotCounter);
        $this->slotCounter++;

        $slot = AppointmentSlot::create([
            'hospital_id' => $this->hospital->id,
            'practitioner_id' => $this->practitioner->id,
            'department_id' => $this->department->id,
            'date' => $starts->toDateString(),
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addMinutes(30),
            'capacity' => 5,
            'booked_count' => 1,
        ]);

        return Appointment::create([
            'hospital_id' => $this->hospital->id,
            'patient_id' => $user->patient->id,
            'practitioner_id' => $this->practitioner->id,
            'department_id' => $this->department->id,
            'slot_id' => $slot->id,
            'scheduled_at' => $starts,
            'status' => Appointment::STATUS_SCHEDULED,
            'payment_mode' => Appointment::PAY_MODE_PHYSICAL,
            'payment_status' => Appointment::PAY_UNPAID,
        ]);
    }

    public function test_patient_confirms_own_scheduled_appointment(): void
    {
        $user = $this->patientUser('+2348000000001');
        $appointment = $this->scheduledAppointment($user);

        $this->actingAs($user)
            ->post("/patient/appointments/{$appointment->id}/confirm")
            ->assertRedirect("/patient/appointments/{$appointment->id}");

        $this->assertNotNull($appointment->fresh()->attendance_confirmed_at);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'attendance_confirmed',
            'subject_id' => $appointment->id,
        ]);
    }

    public function test_patient_cannot_confirm_another_patients_appointment(): void
    {
        $user = $this->patientUser('+2348000000002');
        $other = $this->patientUser('+2348000000003');
        $appointment = $this->scheduledAppointment($other);

        $this->actingAs($user)
            ->post("/patient/appointments/{$appointment->id}/confirm")
            ->assertForbidden();

        $this->assertNull($appointment->fresh()->attendance_confirmed_at);
    }

    public function test_confirm_rejected_for_non_scheduled_appointment(): void
    {
        $user = $this->patientUser('+2348000000004');
        $appointment = $this->scheduledAppointment($user);
        $appointment->update(['status' => Appointment::STATUS_CANCELLED]);

        $this->actingAs($user)
            ->post("/patient/appointments/{$appointment->id}/confirm")
            ->assertStatus(422);

        $this->assertNull($appointment->fresh()->attendance_confirmed_at);
    }

    public function test_signed_link_confirms_without_login(): void
    {
        $user = $this->patientUser('+2348000000005');
        $appointment = $this->scheduledAppointment($user);

        $url = URL::signedRoute(
            'patient.appointments.confirm.link',
            ['appointment' => $appointment->id],
            now()->addDays(7)
        );

        $this->get($url)->assertRedirect('/login/patient');
        $this->assertNotNull($appointment->fresh()->attendance_confirmed_at);
    }

    public function test_signed_link_rejects_tampering(): void
    {
        $user = $this->patientUser('+2348000000006');
        $appointment = $this->scheduledAppointment($user);

        $url = URL::signedRoute(
            'patient.appointments.confirm.link',
            ['appointment' => $appointment->id],
            now()->addDays(7)
        ).'tampered';

        $this->get($url)->assertForbidden();
        $this->assertNull($appointment->fresh()->attendance_confirmed_at);
    }

    public function test_nudge_fires_only_while_unconfirmed_and_dedupes(): void
    {
        $user = $this->patientUser('+2348000000007');
        $tomorrow = Carbon::tomorrow()->setTime(10, 0);
        $this->scheduledAppointment($user, $tomorrow);

        $this->artisan('reminders:send')->assertSuccessful();
        $this->assertSame(1, Notification::where('type', 'attendance_nudge')->count());

        // Second run same day: no duplicate.
        $this->artisan('reminders:send')->assertSuccessful();
        $this->assertSame(1, Notification::where('type', 'attendance_nudge')->count());

        // After confirming, no further nudges.
        Appointment::where('patient_id', $user->patient->id)->update(['attendance_confirmed_at' => now()]);
        Notification::where('type', 'attendance_nudge')->delete();
        $this->artisan('reminders:send')->assertSuccessful();
        $this->assertSame(0, Notification::where('type', 'attendance_nudge')->count());
    }
}
