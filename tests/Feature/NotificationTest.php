<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\Department;
use App\Models\Hospital;
use App\Models\Notification;
use App\Models\NotificationLog;
use App\Models\Patient;
use App\Models\Practitioner;
use App\Models\QueueEntry;
use App\Models\User;
use App\Services\BookingService;
use App\Services\QueueService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
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
            'room_label' => 'Consultation Room 2',
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

    private function bookFor(User $user, string $mode = Appointment::PAY_MODE_PHYSICAL): Appointment
    {
        $starts = Carbon::now()->addDays(2)->setTime(10, 0)->addMinutes(30 * $this->slotCounter);
        $this->slotCounter++;

        $slot = AppointmentSlot::create([
            'hospital_id' => $this->hospital->id,
            'practitioner_id' => $this->practitioner->id,
            'department_id' => $this->department->id,
            'date' => $starts->toDateString(),
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addMinutes(30),
            'capacity' => 5,
        ]);

        return app(BookingService::class)->book(
            $user->patient, $this->department, $this->practitioner, $slot, $mode
        );
    }

    public function test_booking_notifies_patient_and_staff_with_audit(): void
    {
        $receptionist = User::factory()->create([
            'hospital_id' => $this->hospital->id,
            'role' => User::ROLE_RECEPTIONIST,
        ]);
        $user = $this->patientUser('+2348000000001');

        $appointment = $this->bookFor($user);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'appointment_confirmed',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $receptionist->id,
            'type' => 'appointment_booked',
        ]);
        $this->assertDatabaseHas('notification_logs', ['status' => NotificationLog::STATUS_SENT]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'appointment_booked',
            'subject_id' => $appointment->id,
        ]);
    }

    public function test_patient_cancel_notifies_staff_and_staff_cancel_notifies_patient(): void
    {
        $receptionist = User::factory()->create([
            'hospital_id' => $this->hospital->id,
            'role' => User::ROLE_RECEPTIONIST,
        ]);
        $user = $this->patientUser('+2348000000002');
        $bookings = app(BookingService::class);

        $mine = $this->bookFor($user);
        $bookings->cancel($mine, null, $user);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $receptionist->id,
            'type' => 'appointment_cancelled',
        ]);

        $theirs = $this->bookFor($user);
        $bookings->cancel($theirs, 'Schedule clash', $receptionist);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'appointment_cancelled',
        ]);
    }

    public function test_check_in_and_called_notify_patient(): void
    {
        $user = $this->patientUser('+2348000000003');
        $receptionist = User::factory()->create([
            'hospital_id' => $this->hospital->id,
            'role' => User::ROLE_RECEPTIONIST,
        ]);
        $queue = app(QueueService::class);

        $appointment = $this->bookFor($user);
        $appointment->update(['payment_status' => Appointment::PAY_PAID]);
        $queue->checkIn($appointment, $receptionist);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'checked_in',
        ]);

        $entry = QueueEntry::where('appointment_id', $appointment->id)->firstOrFail();
        $queue->call($entry, $receptionist);

        $called = Notification::where('user_id', $user->id)->where('type', 'called')->firstOrFail();
        $this->assertStringContainsString('Consultation Room 2', $called->body);
    }

    public function test_reminders_are_idempotent(): void
    {
        $user = $this->patientUser('+2348000000004');
        $starts = Carbon::tomorrow()->setTime(10, 0);
        $slot = AppointmentSlot::create([
            'hospital_id' => $this->hospital->id,
            'practitioner_id' => $this->practitioner->id,
            'department_id' => $this->department->id,
            'date' => $starts->toDateString(),
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addMinutes(30),
            'capacity' => 5,
        ]);
        app(BookingService::class)->book($user->patient, $this->department, $this->practitioner, $slot, Appointment::PAY_MODE_PHYSICAL);

        $this->artisan('reminders:send')->assertSuccessful();
        $this->artisan('reminders:send')->assertSuccessful();

        $this->assertSame(1, Notification::where('user_id', $user->id)
            ->where('type', 'appointment_reminder_day_before')
            ->count());
    }

    public function test_daily_summary_goes_to_practitioner_with_login(): void
    {
        $practitionerUser = User::factory()->create([
            'hospital_id' => $this->hospital->id,
            'role' => User::ROLE_PRACTITIONER,
        ]);
        $this->practitioner->update(['user_id' => $practitionerUser->id]);

        $this->artisan('summaries:send')->assertSuccessful();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $practitionerUser->id,
            'type' => 'daily_schedule_summary',
        ]);
    }

    public function test_inbox_lists_and_marks_read(): void
    {
        $user = $this->patientUser('+2348000000005');
        $this->bookFor($user);

        $response = $this->actingAs($user)->get('/notifications');
        $response->assertOk();

        $notice = Notification::where('user_id', $user->id)->firstOrFail();
        $this->actingAs($user)->post("/notifications/{$notice->id}/read")->assertRedirect();
        $this->assertNotNull($notice->fresh()->read_at);
    }
}
