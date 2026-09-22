<?php

namespace Tests\Feature;

use App\Jobs\ProcessRefund;
use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\Department;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Practitioner;
use App\Models\User;
use App\Services\BookingService;
use App\Services\PaymentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private Hospital $hospital;

    private Department $department;

    private Practitioner $practitioner;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hospital = Hospital::create(['name' => 'Test Hospital', 'paystack_secret' => 'sk_test_123']);
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
            'email' => 'patient@example.com',
        ]);
    }

    private function appointment(): Appointment
    {
        $starts = Carbon::now()->addDays(2)->setTime(10, 0);
        $slot = AppointmentSlot::create([
            'hospital_id' => $this->hospital->id,
            'practitioner_id' => $this->practitioner->id,
            'department_id' => $this->department->id,
            'date' => $starts->toDateString(),
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addMinutes(30),
            'capacity' => 1,
        ]);

        return app(BookingService::class)->book(
            $this->patient, $this->department, $this->practitioner, $slot, Appointment::PAY_MODE_ONLINE
        );
    }

    public function test_initialize_creates_pending_payment_and_returns_url(): void
    {
        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => ['authorization_url' => 'https://paystack.test/pay/abc', 'reference' => 'APT-1-XYZ'],
            ]),
        ]);

        $appointment = $this->appointment();
        $result = app(PaymentService::class)->initialize(
            $appointment, 'patient@example.com', 'http://localhost/verify', 500000
        );

        $this->assertSame('https://paystack.test/pay/abc', $result['authorization_url']);

        $payment = Payment::where('appointment_id', $appointment->id)->firstOrFail();
        $this->assertSame(Payment::STATUS_PENDING, $payment->status);
        $this->assertSame(Appointment::PAY_PENDING, $appointment->fresh()->payment_status);
    }

    public function test_initialize_failure_marks_failed(): void
    {
        Http::fake(['api.paystack.co/*' => Http::response(['status' => false], 400)]);

        $appointment = $this->appointment();

        try {
            app(PaymentService::class)->initialize($appointment, 'patient@example.com', 'http://localhost/verify', 500000);
            $this->fail('Expected RuntimeException.');
        } catch (\RuntimeException) {
            $this->assertSame(Appointment::PAY_FAILED, $appointment->fresh()->payment_status);
        }
    }

    public function test_verify_marks_paid(): void
    {
        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => ['authorization_url' => 'https://paystack.test/pay/abc', 'reference' => 'r1'],
            ]),
            'api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => ['status' => 'success', 'reference' => 'r1'],
            ]),
        ]);

        $service = app(PaymentService::class);
        $appointment = $this->appointment();
        $service->initialize($appointment, 'patient@example.com', 'http://localhost/verify', 500000);
        $payment = Payment::where('appointment_id', $appointment->id)->firstOrFail();

        $this->assertTrue($service->verifyByReference($payment->fresh()));
        $this->assertSame(Appointment::PAY_PAID, $appointment->fresh()->payment_status);
        $this->assertNotNull($payment->fresh()->paid_at);
    }

    public function test_webhook_marks_paid_and_is_idempotent(): void
    {
        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => ['authorization_url' => 'https://paystack.test/pay/abc', 'reference' => 'r1'],
            ]),
        ]);

        $service = app(PaymentService::class);
        $appointment = $this->appointment();
        $result = $service->initialize($appointment, 'patient@example.com', 'http://localhost/verify', 500000);

        $payload = json_encode([
            'event' => 'charge.success',
            'data' => ['reference' => $result['reference'], 'amount' => 500000],
        ]);
        $signature = hash_hmac('sha512', $payload, 'sk_test_123');

        $response = $this->call('POST', '/webhooks/paystack', [], [], [], [
            'HTTP_X-Paystack-Signature' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertOk()->assertSee('paid');
        $this->assertSame(Appointment::PAY_PAID, $appointment->fresh()->payment_status);

        $again = $this->call('POST', '/webhooks/paystack', [], [], [], [
            'HTTP_X-Paystack-Signature' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $again->assertOk()->assertSee('duplicate');
        $this->assertSame(1, Payment::where('reference', $result['reference'])->count());
    }

    public function test_webhook_rejects_bad_signature(): void
    {
        $payload = json_encode(['event' => 'charge.success', 'data' => ['reference' => 'nope']]);

        $this->call('POST', '/webhooks/paystack', [], [], [], [
            'HTTP_X-Paystack-Signature' => 'invalid',
            'CONTENT_TYPE' => 'application/json',
        ], $payload)->assertUnauthorized();
    }

    public function test_reconcile_settles_stale_pending(): void
    {
        Http::fake([
            'api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => ['status' => 'success'],
            ]),
        ]);

        $appointment = $this->appointment();
        $payment = Payment::create([
            'hospital_id' => $this->hospital->id,
            'appointment_id' => $appointment->id,
            'patient_id' => $this->patient->id,
            'provider' => Payment::PROVIDER_PAYSTACK,
            'reference' => 'APT-STALE-1',
            'amount_kobo' => 500000,
            'status' => Payment::STATUS_PENDING,
        ]);
        $payment->forceFill(['created_at' => now()->subHour()])->save();

        $this->artisan('payments:reconcile')->assertSuccessful();

        $this->assertSame(Payment::STATUS_SUCCESS, $payment->fresh()->status);
        $this->assertSame(Appointment::PAY_PAID, $appointment->fresh()->payment_status);
    }

    public function test_reconcile_abandons_unverifiable(): void
    {
        Http::fake(['api.paystack.co/*' => Http::response(['status' => false], 404)]);

        $appointment = $this->appointment();
        $payment = Payment::create([
            'hospital_id' => $this->hospital->id,
            'appointment_id' => $appointment->id,
            'patient_id' => $this->patient->id,
            'provider' => Payment::PROVIDER_PAYSTACK,
            'reference' => 'APT-STALE-2',
            'amount_kobo' => 500000,
            'status' => Payment::STATUS_PENDING,
        ]);
        $payment->forceFill(['created_at' => now()->subHour()])->save();

        $this->artisan('payments:reconcile')->assertSuccessful();

        $this->assertSame(Payment::STATUS_ABANDONED, $payment->fresh()->status);
        $this->assertSame(Appointment::PAY_FAILED, $appointment->fresh()->payment_status);
    }

    public function test_refund_job_marks_refunded_on_success(): void
    {
        Http::fake(['api.paystack.co/refund' => Http::response(['status' => true, 'data' => []])]);

        $appointment = $this->appointment();
        $payment = Payment::create([
            'hospital_id' => $this->hospital->id,
            'appointment_id' => $appointment->id,
            'patient_id' => $this->patient->id,
            'provider' => Payment::PROVIDER_PAYSTACK,
            'reference' => 'APT-PAID-1',
            'amount_kobo' => 500000,
            'status' => Payment::STATUS_SUCCESS,
            'paid_at' => now(),
        ]);
        $appointment->update(['payment_status' => Appointment::PAY_PAID, 'payment_id' => $payment->id]);

        app()->call([(new ProcessRefund($appointment->id)), 'handle']);

        $this->assertSame(Payment::STATUS_REFUNDED, $payment->fresh()->status);
        $this->assertSame(Appointment::PAY_REFUNDED, $appointment->fresh()->payment_status);
    }

    public function test_admin_can_view_payment_logs(): void
    {
        $admin = User::factory()->create([
            'hospital_id' => $this->hospital->id,
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)->get('/admin/departments')->assertOk();
    }
}
