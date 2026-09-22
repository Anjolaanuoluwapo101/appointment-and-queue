<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Practitioner;
use App\Models\QueueEntry;
use App\Models\User;
use App\Services\BookingService;
use App\Services\QueueService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportAuditTest extends TestCase
{
    use RefreshDatabase;

    private Hospital $hospital;

    private Department $department;

    private Practitioner $practitioner;

    private User $admin;

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
        $this->admin = User::factory()->create([
            'hospital_id' => $this->hospital->id,
            'role' => User::ROLE_ADMIN,
        ]);
    }

    private function seedVisit(string $phone, string $status = Appointment::STATUS_SCHEDULED): Appointment
    {
        $patient = Patient::create([
            'hospital_id' => $this->hospital->id,
            'full_name' => "Patient {$phone}",
            'phone' => $phone,
        ]);
        $starts = Carbon::now()->addDays(2)->setTime(10, 0)->addMinutes(random_int(0, 600));
        $slot = AppointmentSlot::create([
            'hospital_id' => $this->hospital->id,
            'practitioner_id' => $this->practitioner->id,
            'department_id' => $this->department->id,
            'date' => $starts->toDateString(),
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addMinutes(30),
            'capacity' => 10,
        ]);

        $appointment = app(BookingService::class)->book(
            $patient, $this->department, $this->practitioner, $slot, Appointment::PAY_MODE_PHYSICAL
        );

        if ($status === Appointment::STATUS_COMPLETED) {
            $appointment->update(['payment_status' => Appointment::PAY_PAID]);
            $queue = app(QueueService::class);
            $queue->checkIn($appointment, $this->admin);
            $entry = QueueEntry::where('appointment_id', $appointment->id)->firstOrFail();
            $queue->call($entry, $this->admin);
            $queue->complete($entry->fresh(), $this->admin);
        }

        return $appointment->fresh();
    }

    public function test_admin_dashboard_loads_with_stats(): void
    {
        $this->seedVisit('+2348000000001');

        $this->actingAs($this->admin)->get('/admin/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('payments'));
    }

    public function test_reports_page_and_excel_export(): void
    {
        $this->seedVisit('+2348000000002', Appointment::STATUS_COMPLETED);

        $from = Carbon::now()->subDays(30)->toDateString();
        $to = Carbon::now()->addDays(30)->toDateString();

        $this->actingAs($this->admin)->get("/admin/reports?from={$from}&to={$to}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('report.appointments')
                ->has('report.queue')
                ->has('report.payments'));

        $export = $this->actingAs($this->admin)->get("/admin/reports/export?from={$from}&to={$to}");
        $export->assertOk();
        $this->assertStringContainsString(
            'spreadsheetml',
            (string) $export->headers->get('Content-Type')
        );
    }

    public function test_audit_log_view_export_and_access_control(): void
    {
        $this->seedVisit('+2348000000003');

        $this->assertTrue(AuditLog::where('action', 'appointment_booked')->exists());

        $this->actingAs($this->admin)->get('/admin/audit-log')->assertOk();

        $export = $this->actingAs($this->admin)->get('/admin/audit-log/export');
        $export->assertOk();
        $this->assertStringContainsString(
            'spreadsheetml',
            (string) $export->headers->get('Content-Type')
        );

        $receptionist = User::factory()->create([
            'hospital_id' => $this->hospital->id,
            'role' => User::ROLE_RECEPTIONIST,
        ]);
        $this->actingAs($receptionist)->get('/admin/audit-log')->assertForbidden();
    }

    public function test_search_finds_patient_and_is_hospital_scoped(): void
    {
        Patient::create([
            'hospital_id' => $this->hospital->id,
            'full_name' => 'Ada Search',
            'phone' => '+2348000000099',
        ]);

        $other = Hospital::create(['name' => 'Other Hospital']);
        Patient::create([
            'hospital_id' => $other->id,
            'full_name' => 'Ada Search',
            'phone' => '+2348000000098',
        ]);

        $receptionist = User::factory()->create([
            'hospital_id' => $this->hospital->id,
            'role' => User::ROLE_RECEPTIONIST,
        ]);

        $response = $this->actingAs($receptionist)->get('/staff/search?q=Ada%20Search');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('patients.0.phone', '+2348000000099')
            ->has('patients', 1));
    }
}
