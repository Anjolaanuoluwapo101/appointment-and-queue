<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Department;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaystackWebhookHardeningTest extends TestCase
{
    use RefreshDatabase;

    private Hospital $hospital;

    private Department $department;

    private Patient $patient;

    private Appointment $appointment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hospital = Hospital::create([
            'name' => 'General Hospital',
            'is_active' => true,
            'paystack_secret' => 'sk_test_123456789',
        ]);

        $this->department = Department::create([
            'hospital_id' => $this->hospital->id,
            'name' => 'Cardiology',
            'queue_prefix' => 'C',
            'payment_mode' => Department::PAYMENT_ALLOW_BOTH,
            'base_fee_kobo' => 500000,
        ]);

        $user = User::factory()->create(['hospital_id' => $this->hospital->id, 'role' => User::ROLE_PATIENT]);
        $this->patient = Patient::create([
            'hospital_id' => $this->hospital->id,
            'user_id' => $user->id,
            'full_name' => 'John Doe',
            'phone' => '08012345678',
        ]);

        $this->appointment = Appointment::create([
            'hospital_id' => $this->hospital->id,
            'patient_id' => $this->patient->id,
            'department_id' => $this->department->id,
            'scheduled_at' => now()->addDay(),
            'status' => Appointment::STATUS_SCHEDULED,
            'payment_mode' => Appointment::PAY_MODE_ONLINE,
            'payment_status' => Appointment::PAY_UNPAID,
        ]);
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        config(['services.paystack.secret' => 'sk_test_123456789']);

        $payload = [
            'event' => 'charge.success',
            'data' => [
                'reference' => 'PAY-TEST-123',
                'status' => 'success',
                'amount' => 500000,
            ],
        ];

        $response = $this->postJson('/webhooks/paystack', $payload, [
            'x-paystack-signature' => 'invalid_signature_hash',
        ]);

        $response->assertStatus(401);
    }

    public function test_webhook_accepts_valid_signature_and_is_idempotent(): void
    {
        $secret = 'sk_test_123456789';
        config(['services.paystack.secret' => $secret]);

        $payment = Payment::create([
            'hospital_id' => $this->hospital->id,
            'patient_id' => $this->patient->id,
            'appointment_id' => $this->appointment->id,
            'provider' => Payment::PROVIDER_PAYSTACK,
            'reference' => 'PAY-TEST-999',
            'amount_kobo' => 500000,
            'status' => Payment::STATUS_PENDING,
        ]);
        $this->appointment->update(['payment_id' => $payment->id]);

        $payload = json_encode([
            'event' => 'charge.success',
            'data' => [
                'reference' => 'PAY-TEST-999',
                'status' => 'success',
                'amount' => 500000,
            ],
        ]);

        $signature = hash_hmac('sha512', $payload, $secret);

        // First webhook call
        $response1 = $this->call('POST', '/webhooks/paystack', [], [], [], [
            'HTTP_X_PAYSTACK_SIGNATURE' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response1->assertStatus(200);
        $this->assertSame(Payment::STATUS_SUCCESS, $payment->fresh()->status);
        $this->assertSame(Appointment::PAY_PAID, $this->appointment->fresh()->payment_status);

        // Duplicate replay webhook call should be idempotent and succeed without duplicating record
        $response2 = $this->call('POST', '/webhooks/paystack', [], [], [], [
            'HTTP_X_PAYSTACK_SIGNATURE' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response2->assertStatus(200);
        $this->assertSame(1, Payment::where('reference', 'PAY-TEST-999')->count());
    }

    public function test_webhook_handles_non_existent_reference_gracefully(): void
    {
        $secret = 'sk_test_123456789';
        config(['services.paystack.secret' => $secret]);

        $payload = json_encode([
            'event' => 'charge.success',
            'data' => [
                'reference' => 'NON-EXISTENT-REF',
                'status' => 'success',
                'amount' => 500000,
            ],
        ]);

        $signature = hash_hmac('sha512', $payload, $secret);

        $response = $this->call('POST', '/webhooks/paystack', [], [], [], [
            'HTTP_X_PAYSTACK_SIGNATURE' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertStatus(200);
    }
}
