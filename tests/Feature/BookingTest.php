<?php

namespace Tests\Feature;

use App\Jobs\ProcessRefund;
use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\ConsultationFee;
use App\Models\Department;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Practitioner;
use App\Services\BookingService;
use App\Services\FeeResolver;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    private Hospital $hospital;

    private Department $department;

    private Practitioner $practitioner;

    private Patient $patient;

    private BookingService $bookings;

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
        $this->patient = Patient::create([
            'hospital_id' => $this->hospital->id,
            'full_name' => 'Test Patient',
            'phone' => '+2348000000001',
        ]);
        $this->bookings = app(BookingService::class);
    }

    private function slot(array $overrides = []): AppointmentSlot
    {
        $starts = Carbon::now()->addDays(2)->setTime(10, 0);

        return AppointmentSlot::create(array_merge([
            'hospital_id' => $this->hospital->id,
            'practitioner_id' => $this->practitioner->id,
            'department_id' => $this->department->id,
            'date' => $starts->toDateString(),
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addMinutes(30),
            'capacity' => 1,
            'booked_count' => 0,
        ], $overrides));
    }

    public function test_book_holds_slot_and_creates_unpaid_appointment(): void
    {
        $appointment = $this->bookings->book(
            $this->patient, $this->department, $this->practitioner, $this->slot(), Appointment::PAY_MODE_PHYSICAL
        );

        $this->assertSame(Appointment::STATUS_SCHEDULED, $appointment->status);
        $this->assertSame(Appointment::PAY_UNPAID, $appointment->payment_status);
        $this->assertSame(1, $appointment->slot->fresh()->booked_count);
    }

    public function test_book_without_practitioner_uses_slot_practitioner(): void
    {
        $appointment = $this->bookings->book(
            $this->patient, $this->department, null, $this->slot(), Appointment::PAY_MODE_PHYSICAL
        );

        $this->assertSame($this->practitioner->id, $appointment->practitioner_id);
    }

    public function test_book_full_slot_fails(): void
    {
        $slot = $this->slot();

        $this->bookings->book($this->patient, $this->department, $this->practitioner, $slot, Appointment::PAY_MODE_PHYSICAL);

        $other = Patient::create(['hospital_id' => $this->hospital->id, 'full_name' => 'Other', 'phone' => '+2348000000002']);

        $this->expectException(ValidationException::class);
        $this->bookings->book($other, $this->department, $this->practitioner, $slot->fresh(), Appointment::PAY_MODE_PHYSICAL);
    }

    public function test_book_within_cutoff_fails(): void
    {
        $starts = Carbon::now()->addMinutes(30);
        $slot = $this->slot(['starts_at' => $starts, 'ends_at' => $starts->copy()->addMinutes(30), 'date' => $starts->toDateString()]);

        $this->expectException(ValidationException::class);
        $this->bookings->book($this->patient, $this->department, $this->practitioner, $slot, Appointment::PAY_MODE_PHYSICAL);
    }

    public function test_payment_mode_must_match_department(): void
    {
        $this->department->update(['payment_mode' => Department::PAYMENT_PHYSICAL_ONLY]);

        $this->expectException(ValidationException::class);
        $this->bookings->book($this->patient, $this->department, $this->practitioner, $this->slot(), Appointment::PAY_MODE_ONLINE);
    }

    public function test_unavailable_practitioner_cannot_be_booked(): void
    {
        $this->practitioner->update(['availability' => Practitioner::AVAILABILITY_ON_LEAVE]);

        $this->expectException(ValidationException::class);
        $this->bookings->book($this->patient, $this->department, $this->practitioner->fresh(), $this->slot(), Appointment::PAY_MODE_PHYSICAL);
    }

    public function test_reschedule_moves_hold_and_counts(): void
    {
        $old = $this->slot();
        $appointment = $this->bookings->book($this->patient, $this->department, $this->practitioner, $old, Appointment::PAY_MODE_PHYSICAL);

        $starts = Carbon::now()->addDays(3)->setTime(11, 0);
        $new = $this->slot(['starts_at' => $starts, 'ends_at' => $starts->copy()->addMinutes(30), 'date' => $starts->toDateString()]);

        $moved = $this->bookings->reschedule($appointment, $new);

        $this->assertSame($new->id, $moved->slot_id);
        $this->assertSame(1, $moved->reschedule_count);
        $this->assertSame(0, $old->fresh()->booked_count);
        $this->assertSame(1, $new->fresh()->booked_count);
    }

    public function test_cancel_frees_slot_and_records_reason(): void
    {
        Queue::fake();

        $slot = $this->slot();
        $appointment = $this->bookings->book($this->patient, $this->department, $this->practitioner, $slot, Appointment::PAY_MODE_PHYSICAL);

        $cancelled = $this->bookings->cancel($appointment, 'Patient request');

        $this->assertSame(Appointment::STATUS_CANCELLED, $cancelled->status);
        $this->assertSame('Patient request', $cancelled->cancellation_reason);
        $this->assertSame(0, $slot->fresh()->booked_count);
        Queue::assertNotPushed(ProcessRefund::class);
    }

    public function test_cancel_paid_appointment_dispatches_refund(): void
    {
        Queue::fake();

        $appointment = $this->bookings->book($this->patient, $this->department, $this->practitioner, $this->slot(), Appointment::PAY_MODE_ONLINE);
        $appointment->update(['payment_status' => Appointment::PAY_PAID]);

        $this->bookings->cancel($appointment->fresh(), 'Patient request');

        Queue::assertPushed(ProcessRefund::class);
    }

    public function test_no_show_does_not_refund(): void
    {
        Queue::fake();

        $appointment = $this->bookings->book($this->patient, $this->department, $this->practitioner, $this->slot(), Appointment::PAY_MODE_ONLINE);
        $appointment->update(['payment_status' => Appointment::PAY_PAID]);

        $marked = $this->bookings->markNoShow($appointment->fresh());

        $this->assertSame(Appointment::STATUS_NO_SHOW, $marked->status);
        $this->assertSame(Appointment::PAY_PAID, $marked->payment_status);
        Queue::assertNotPushed(ProcessRefund::class);
    }

    public function test_fee_resolver_prefers_override_then_base_then_department(): void
    {
        $fees = app(FeeResolver::class);

        $this->assertSame(500000, $fees->resolve($this->hospital->id, $this->department->id, $this->practitioner->id));

        ConsultationFee::create([
            'hospital_id' => $this->hospital->id,
            'department_id' => $this->department->id,
            'practitioner_id' => $this->practitioner->id,
            'amount_kobo' => 900000,
        ]);

        $this->assertSame(900000, $fees->resolve($this->hospital->id, $this->department->id, $this->practitioner->id));
    }
}
