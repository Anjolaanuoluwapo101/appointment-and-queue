<?php

namespace Tests\Feature;

use App\Models\AppointmentSlot;
use App\Models\Department;
use App\Models\Hospital;
use App\Models\Practitioner;
use App\Models\PractitionerSchedule;
use App\Models\ScheduleException;
use App\Models\User;
use App\Services\SlotGenerator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchedulingTest extends TestCase
{
    use RefreshDatabase;

    private Hospital $hospital;

    private User $admin;

    private Department $department;

    private Practitioner $practitioner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hospital = Hospital::create(['name' => 'Test Hospital']);
        $this->admin = User::factory()->create([
            'hospital_id' => $this->hospital->id,
            'role' => User::ROLE_ADMIN,
        ]);
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

    private function nextWeekday(int $weekday): Carbon
    {
        $date = Carbon::now('Africa/Lagos')->startOfDay();
        do {
            $date->addDay();
        } while ((int) $date->format('w') !== $weekday);

        return $date;
    }

    private function schedulePayload(array $overrides = []): array
    {
        return array_merge([
            'practitioner_id' => $this->practitioner->id,
            'department_id' => $this->department->id,
            'weekday' => 1,
            'start_time' => '09:00',
            'end_time' => '13:00',
            'slot_duration_minutes' => 30,
            'max_per_slot' => 1,
        ], $overrides);
    }

    public function test_admin_creates_schedule_and_slots_generate(): void
    {
        $monday = $this->nextWeekday(1);

        $this->actingAs($this->admin)
            ->post('/admin/schedules', $this->schedulePayload())
            ->assertRedirect('/admin/schedules');

        $slots = AppointmentSlot::where('practitioner_id', $this->practitioner->id)
            ->whereDate('date', $monday->toDateString())
            ->orderBy('starts_at')
            ->get();

        $this->assertCount(8, $slots);
        $this->assertSame('09:00', $slots->first()->starts_at->format('H:i'));
        $this->assertSame('13:00', $slots->last()->ends_at->format('H:i'));
    }

    public function test_break_period_excluded_from_slots(): void
    {
        $monday = $this->nextWeekday(1);

        $this->actingAs($this->admin)->post('/admin/schedules', $this->schedulePayload([
            'break_start' => '10:00',
            'break_end' => '10:30',
        ]))->assertRedirect('/admin/schedules');

        $slots = AppointmentSlot::where('practitioner_id', $this->practitioner->id)
            ->whereDate('date', $monday->toDateString())
            ->orderBy('starts_at')
            ->get();

        $this->assertCount(7, $slots);
        $this->assertFalse($slots->contains(fn ($slot) => $slot->starts_at->format('H:i') === '10:00'));
    }

    public function test_day_off_exception_suppresses_slots(): void
    {
        $monday = $this->nextWeekday(1);

        $this->actingAs($this->admin)->post('/admin/schedules', $this->schedulePayload())
            ->assertRedirect('/admin/schedules');

        AppointmentSlot::where('practitioner_id', $this->practitioner->id)->delete();

        $this->actingAs($this->admin)->post('/admin/exceptions', [
            'practitioner_id' => $this->practitioner->id,
            'department_id' => $this->department->id,
            'date' => $monday->toDateString(),
            'type' => ScheduleException::TYPE_DAY_OFF,
            'reason' => 'Sick day',
        ])->assertRedirect('/admin/exceptions');

        $generator = app(SlotGenerator::class);
        $generator->generateForHospital($this->hospital, 14);

        $this->assertSame(0, AppointmentSlot::where('practitioner_id', $this->practitioner->id)
            ->whereDate('date', $monday->toDateString())
            ->count());
    }

    public function test_overlapping_schedule_rejected(): void
    {
        $this->actingAs($this->admin)->post('/admin/schedules', $this->schedulePayload())
            ->assertRedirect('/admin/schedules');

        $this->actingAs($this->admin)->post('/admin/schedules', $this->schedulePayload([
            'start_time' => '12:00',
            'end_time' => '15:00',
        ]))->assertSessionHasErrors('schedule');
    }

    public function test_practitioner_must_belong_to_department(): void
    {
        $other = Department::create([
            'hospital_id' => $this->hospital->id,
            'name' => 'Dental',
            'queue_prefix' => 'T',
            'payment_mode' => Department::PAYMENT_ALLOW_BOTH,
            'base_fee_kobo' => 0,
        ]);

        $this->actingAs($this->admin)->post('/admin/schedules', $this->schedulePayload([
            'department_id' => $other->id,
        ]))->assertSessionHasErrors('department_id');
    }

    public function test_schedule_update_blocked_with_bookings(): void
    {
        $this->actingAs($this->admin)->post('/admin/schedules', $this->schedulePayload())
            ->assertRedirect('/admin/schedules');

        $schedule = PractitionerSchedule::firstOrFail();
        $slot = AppointmentSlot::where('schedule_id', $schedule->id)->where('booked_count', 0)->firstOrFail();
        $slot->update(['booked_count' => 1]);

        $this->actingAs($this->admin)->put("/admin/schedules/{$schedule->id}", array_merge(
            $this->schedulePayload(),
            ['end_time' => '12:00', 'is_active' => true]
        ))->assertSessionHasErrors('schedule');
    }

    public function test_schedule_update_regenerates_unbooked_slots(): void
    {
        $this->actingAs($this->admin)->post('/admin/schedules', $this->schedulePayload())
            ->assertRedirect('/admin/schedules');

        $schedule = PractitionerSchedule::firstOrFail();
        $before = AppointmentSlot::where('schedule_id', $schedule->id)->count();
        $this->assertGreaterThan(0, $before);

        $this->actingAs($this->admin)->put("/admin/schedules/{$schedule->id}", array_merge(
            $this->schedulePayload(),
            ['slot_duration_minutes' => 60, 'is_active' => true]
        ))->assertRedirect('/admin/schedules');

        $monday = $this->nextWeekday(1);
        $this->assertCount(4, AppointmentSlot::where('schedule_id', $schedule->id)
            ->whereDate('date', $monday->toDateString())
            ->get());
    }

    public function test_unavailable_practitioner_generates_no_slots(): void
    {
        $this->practitioner->update(['availability' => Practitioner::AVAILABILITY_ON_LEAVE]);

        $generator = app(SlotGenerator::class);
        PractitionerSchedule::create([
            'hospital_id' => $this->hospital->id,
            'practitioner_id' => $this->practitioner->id,
            'department_id' => $this->department->id,
            'weekday' => 1,
            'start_time' => '09:00',
            'end_time' => '13:00',
            'slot_duration_minutes' => 30,
            'max_per_slot' => 1,
        ]);

        $this->assertSame(0, $generator->generateForHospital($this->hospital, 14));
    }

    public function test_slot_generation_is_idempotent(): void
    {
        $this->actingAs($this->admin)->post('/admin/schedules', $this->schedulePayload())
            ->assertRedirect('/admin/schedules');

        $count = AppointmentSlot::count();

        $this->artisan('slots:generate', ['--hospital' => $this->hospital->id, '--days' => 30])
            ->assertSuccessful();

        $this->assertSame($count, AppointmentSlot::count());
    }
}
