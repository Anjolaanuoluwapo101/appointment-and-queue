<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\Department;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Practitioner;
use App\Models\QueueEntry;
use App\Models\QueueNumberSequence;
use App\Models\User;
use App\Services\BookingService;
use App\Services\QueueService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class QueueTest extends TestCase
{
    use RefreshDatabase;

    private Hospital $hospital;

    private Department $department;

    private Practitioner $practitioner;

    private User $receptionist;

    private QueueService $queue;

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
        $this->receptionist = User::factory()->create([
            'hospital_id' => $this->hospital->id,
            'role' => User::ROLE_RECEPTIONIST,
        ]);
        $this->queue = app(QueueService::class);
    }

    private function patient(string $phone): Patient
    {
        return Patient::create([
            'hospital_id' => $this->hospital->id,
            'full_name' => "Patient {$phone}",
            'phone' => $phone,
        ]);
    }

    private int $slotCounter = 0;

    private function appointment(Patient $patient, string $mode = Appointment::PAY_MODE_PHYSICAL): Appointment
    {
        // Offset each slot so the practitioner/dept/starts_at unique holds.
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

        return app(BookingService::class)->book($patient, $this->department, $this->practitioner, $slot, $mode);
    }

    public function test_paid_check_in_auto_queues_with_number(): void
    {
        $appointment = $this->appointment($this->patient('+2348000000001'));
        $appointment->update(['payment_status' => Appointment::PAY_PAID]);

        $checked = $this->queue->checkIn($appointment, $this->receptionist);

        $this->assertSame(Appointment::STATUS_IN_QUEUE, $checked->status);

        $entry = QueueEntry::where('appointment_id', $appointment->id)->firstOrFail();
        $this->assertSame('C001', $entry->queue_number);
        $this->assertSame(QueueEntry::STATUS_WAITING, $entry->status);
    }

    public function test_unpaid_check_in_parks_at_pending_clearance(): void
    {
        $appointment = $this->appointment($this->patient('+2348000000002'));

        $checked = $this->queue->checkIn($appointment, $this->receptionist);

        $this->assertSame(Appointment::STATUS_PENDING_CLEARANCE, $checked->status);
        $this->assertSame(0, QueueEntry::where('appointment_id', $appointment->id)->count());
    }

    public function test_manual_clearance_records_payment_and_queues(): void
    {
        $appointment = $this->appointment($this->patient('+2348000000003'));
        $this->queue->checkIn($appointment, $this->receptionist);

        $cleared = $this->queue->clearManually($appointment->fresh(), 'R-100', 'cash', $this->receptionist);

        $this->assertSame(Appointment::STATUS_IN_QUEUE, $cleared->status);
        $this->assertSame(Appointment::PAY_PAID, $cleared->payment_status);
        $this->assertSame('R-100', $cleared->payment->receipt_no);

        $entry = QueueEntry::where('appointment_id', $appointment->id)->firstOrFail();
        $this->assertSame('C001', $entry->queue_number);
    }

    public function test_numbers_sequence_and_reset_daily(): void
    {
        QueueNumberSequence::create([
            'hospital_id' => $this->hospital->id,
            'department_id' => $this->department->id,
            'queue_date' => Carbon::yesterday()->toDateString(),
            'last_number' => 5,
        ]);

        foreach (['+2348000000011', '+2348000000012'] as $phone) {
            $appointment = $this->appointment($this->patient($phone));
            $appointment->update(['payment_status' => Appointment::PAY_PAID]);
            $this->queue->checkIn($appointment, $this->receptionist);
        }

        $this->assertSame(
            ['C001', 'C002'],
            QueueEntry::orderBy('id')->pluck('queue_number')->all()
        );
    }

    public function test_call_next_is_fifo_and_recalled_jumps_ahead(): void
    {
        $entries = [];
        foreach (['+2348000000021', '+2348000000022', '+2348000000023'] as $phone) {
            $appointment = $this->appointment($this->patient($phone));
            $appointment->update(['payment_status' => Appointment::PAY_PAID]);
            $this->queue->checkIn($appointment, $this->receptionist);
            $entries[] = QueueEntry::where('appointment_id', $appointment->id)->firstOrFail();
        }

        $first = $this->queue->callNext($this->department, null, $this->receptionist);
        $this->assertSame($entries[0]->id, $first->id);

        $this->queue->skip($first, $this->receptionist);

        $second = $this->queue->callNext($this->department, null, $this->receptionist);
        $this->assertSame($entries[1]->id, $second->id);

        $this->queue->recall($entries[0]->fresh(), $this->receptionist);
        $this->queue->complete($second, $this->receptionist);

        $third = $this->queue->callNext($this->department, null, $this->receptionist);
        $this->assertSame($entries[0]->id, $third->id);
    }

    public function test_consultation_lifecycle_and_appointment_completion(): void
    {
        $appointment = $this->appointment($this->patient('+2348000000031'));
        $appointment->update(['payment_status' => Appointment::PAY_PAID]);
        $this->queue->checkIn($appointment, $this->receptionist);
        $entry = QueueEntry::where('appointment_id', $appointment->id)->firstOrFail();

        $this->queue->call($entry, $this->receptionist);
        $this->assertSame(QueueEntry::STATUS_CALLED, $entry->fresh()->status);

        $this->queue->beginConsultation($entry->fresh(), $this->receptionist);
        $this->assertSame(QueueEntry::STATUS_IN_CONSULTATION, $entry->fresh()->status);

        $this->queue->complete($entry->fresh(), $this->receptionist);
        $this->assertSame(QueueEntry::STATUS_COMPLETED, $entry->fresh()->status);
        $this->assertSame(Appointment::STATUS_COMPLETED, $appointment->fresh()->status);
    }

    public function test_cancelled_entry_stays_visible_and_appointment_intact(): void
    {
        $appointment = $this->appointment($this->patient('+2348000000041'));
        $appointment->update(['payment_status' => Appointment::PAY_PAID]);
        $this->queue->checkIn($appointment, $this->receptionist);
        $entry = QueueEntry::where('appointment_id', $appointment->id)->firstOrFail();

        $this->queue->cancelEntry($entry, 'Duplicate', $this->receptionist);

        $this->assertSame(QueueEntry::STATUS_CANCELLED, $entry->fresh()->status);
        $this->assertSame('Duplicate', $entry->fresh()->cancel_reason);
        $this->assertSame(Appointment::STATUS_IN_QUEUE, $appointment->fresh()->status);

        $snapshot = $this->queue->snapshot($this->department);
        $this->assertCount(1, $snapshot['cancelled']);
    }

    public function test_invalid_transitions_rejected(): void
    {
        $appointment = $this->appointment($this->patient('+2348000000051'));

        try {
            $this->queue->clearManually($appointment, 'R-1', 'cash', $this->receptionist);
            $this->fail('Expected ValidationException.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        $this->expectException(ValidationException::class);
        $this->queue->checkIn($appointment, $this->receptionist);
        $this->queue->checkIn($appointment->fresh(), $this->receptionist);
    }

    public function test_walk_in_creates_patient_and_queues(): void
    {
        $entry = $this->queue->walkIn(
            $this->department,
            $this->practitioner->id,
            ['full_name' => 'Walk In', 'phone' => '+2348000000061'],
            'R-200',
            'cash',
            $this->receptionist
        );

        $this->assertSame('C001', $entry->queue_number);
        $this->assertTrue($entry->appointment->is_walk_in);
        $this->assertSame('Walk In', $entry->patient->full_name);

        $again = $this->queue->walkIn(
            $this->department,
            null,
            ['full_name' => 'Walk In', 'phone' => '+2348000000061'],
            'R-201',
            'pos',
            $this->receptionist
        );

        $this->assertSame($entry->patient_id, $again->patient_id);
        $this->assertSame('C002', $again->queue_number);
    }

    public function test_snapshot_and_patient_position(): void
    {
        $this->department->update(['room_label' => 'Consultation Room 2']);

        $mine = $this->appointment($this->patient('+2348000000071'));
        $mine->update(['payment_status' => Appointment::PAY_PAID]);
        $this->queue->checkIn($mine, $this->receptionist);

        $other = $this->appointment($this->patient('+2348000000072'));
        $other->update(['payment_status' => Appointment::PAY_PAID]);
        $this->queue->checkIn($other, $this->receptionist);

        $snapshot = $this->queue->snapshot($this->department);
        $this->assertCount(2, $snapshot['waiting']);
        $this->assertNull($snapshot['serving']);

        $otherEntry = QueueEntry::where('appointment_id', $other->id)->firstOrFail();
        $this->queue->call($otherEntry, $this->receptionist);

        $mineEntry = QueueEntry::where('appointment_id', $mine->id)->firstOrFail();
        $position = $this->queue->positionFor($mineEntry->fresh());

        $this->assertSame(0, $position['patients_ahead']);
        $this->assertSame($otherEntry->fresh()->queue_number, $position['serving']);
        $this->assertSame('Consultation Room 2', $position['room']);
    }

    public function test_practitioner_cannot_check_in_but_can_call_own(): void
    {
        $practitionerUser = User::factory()->create([
            'hospital_id' => $this->hospital->id,
            'role' => User::ROLE_PRACTITIONER,
        ]);
        $this->practitioner->update(['user_id' => $practitionerUser->id]);

        $appointment = $this->appointment($this->patient('+2348000000081'));

        $this->actingAs($practitionerUser)->post("/staff/appointments/{$appointment->id}/check-in")
            ->assertForbidden();

        $appointment->update(['payment_status' => Appointment::PAY_PAID]);
        $this->queue->checkIn($appointment, $this->receptionist);
        $entry = QueueEntry::where('appointment_id', $appointment->id)->firstOrFail();

        $this->actingAs($practitionerUser)->post("/staff/queue/{$entry->id}/call")->assertRedirect();
        $this->assertSame(QueueEntry::STATUS_CALLED, $entry->fresh()->status);
    }

    public function test_display_board_is_public(): void
    {
        $this->get("/display/queue/{$this->department->id}")->assertOk();
    }

    public function test_patient_show_includes_live_queue(): void
    {
        $user = User::factory()->create([
            'hospital_id' => $this->hospital->id,
            'role' => User::ROLE_PATIENT,
        ]);
        $patient = Patient::create([
            'hospital_id' => $this->hospital->id,
            'user_id' => $user->id,
            'full_name' => 'Queue Patient',
            'phone' => '+2348000000091',
        ]);

        $appointment = $this->appointment($patient);
        $appointment->update(['payment_status' => Appointment::PAY_PAID]);
        $this->queue->checkIn($appointment, $this->receptionist);

        $response = $this->actingAs($user)->get("/patient/appointments/{$appointment->id}");
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('queue')
            ->where('queue.queue_number', 'C001')
        );
    }
}
