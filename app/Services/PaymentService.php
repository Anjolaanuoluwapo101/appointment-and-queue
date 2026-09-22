<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Hospital;
use App\Models\Payment;
use App\Models\PaymentLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Paystack-only collections (PRD §27). Amounts always resolved server-side;
 * provider references are unique for webhook idempotency. Emits MVP
 * payment notices and audit entries.
 */
class PaymentService
{
    private const BASE_URL = 'https://api.paystack.co';

    public function __construct(private NotificationService $notices) {}

    public function secret(Hospital $hospital): ?string
    {
        return $hospital->paystack_secret ?? config('services.paystack.secret');
    }

    /**
     * @return array{authorization_url: string, reference: string}
     */
    public function initialize(Appointment $appointment, string $email, string $callbackUrl, int $amountKobo): array
    {
        $reference = 'APT-'.$appointment->id.'-'.Str::upper(Str::random(8));

        $payment = Payment::create([
            'hospital_id' => $appointment->hospital_id,
            'appointment_id' => $appointment->id,
            'patient_id' => $appointment->patient_id,
            'provider' => Payment::PROVIDER_PAYSTACK,
            'reference' => $reference,
            'amount_kobo' => $amountKobo,
            'status' => Payment::STATUS_PENDING,
        ]);

        $response = Http::withToken((string) $this->secret($appointment->hospital))
            ->post(self::BASE_URL.'/transaction/initialize', [
                'email' => $email,
                'amount' => $amountKobo,
                'reference' => $reference,
                'callback_url' => $callbackUrl,
                'metadata' => ['appointment_id' => $appointment->id],
            ]);

        PaymentLog::create([
            'hospital_id' => $appointment->hospital_id,
            'payment_id' => $payment->id,
            'event' => 'initialize',
            'payload' => ['request' => ['email' => $email, 'amount' => $amountKobo, 'reference' => $reference], 'response' => $response->json()],
            'result' => $response->successful() ? 'ok' : 'failed',
        ]);

        if (! $response->successful() || ! ($response->json('status') ?? false)) {
            $payment->update(['status' => Payment::STATUS_FAILED]);
            $appointment->update(['payment_status' => Appointment::PAY_FAILED]);

            $this->notices->send(
                $appointment->patient->user, $appointment->hospital_id, 'payment_failed',
                'Payment failed',
                'Your online payment could not be started. Please retry.',
                "/patient/appointments/{$appointment->id}"
            );

            throw new \RuntimeException('Paystack initialization failed.');
        }

        $appointment->update(['payment_status' => Appointment::PAY_PENDING, 'payment_id' => $payment->id]);

        return [
            'authorization_url' => (string) $response->json('data.authorization_url'),
            'reference' => $reference,
        ];
    }

    public function verifyByReference(Payment $payment): bool
    {
        $response = Http::withToken((string) $this->secret($payment->appointment->hospital))
            ->get(self::BASE_URL.'/transaction/verify/'.$payment->reference);

        PaymentLog::create([
            'hospital_id' => $payment->hospital_id,
            'payment_id' => $payment->id,
            'event' => 'verify',
            'payload' => $response->json(),
            'result' => $response->json('data.status'),
        ]);

        if ($response->successful() && $response->json('data.status') === 'success') {
            $this->markPaid($payment, false);

            return true;
        }

        return false;
    }

    public function refund(Payment $payment): bool
    {
        $response = Http::withToken((string) $this->secret($payment->appointment->hospital))
            ->post(self::BASE_URL.'/refund', [
                'transaction' => $payment->reference,
                'amount' => $payment->amount_kobo,
            ]);

        PaymentLog::create([
            'hospital_id' => $payment->hospital_id,
            'payment_id' => $payment->id,
            'event' => 'refund',
            'payload' => $response->json(),
            'result' => ($response->successful() && ($response->json('status') ?? false)) ? 'ok' : 'failed',
        ]);

        return $response->successful() && ($response->json('status') ?? false);
    }

    public function verifyWebhookSignature(string $payload, string $signature, Hospital $hospital): bool
    {
        return hash_equals(
            hash_hmac('sha512', $payload, (string) $this->secret($hospital)),
            $signature
        );
    }

    /**
     * Idempotent: repeat deliveries for an already-paid reference are
     * acknowledged without state changes.
     */
    public function handleWebhook(array $payload): string
    {
        $reference = $payload['data']['reference'] ?? null;
        $event = $payload['event'] ?? null;

        if ($reference === null || $event !== 'charge.success') {
            return 'ignored';
        }

        $payment = Payment::where('reference', $reference)->first();

        if ($payment === null) {
            return 'unknown_reference';
        }

        if ($payment->isSuccessful()) {
            PaymentLog::create([
                'hospital_id' => $payment->hospital_id,
                'payment_id' => $payment->id,
                'event' => 'webhook_duplicate',
                'payload' => $payload,
                'result' => 'acknowledged',
            ]);

            return 'duplicate';
        }

        $this->markPaid($payment, true);

        return 'paid';
    }

    public function markPaid(Payment $payment, bool $viaWebhook): void
    {
        $payment->update([
            'status' => Payment::STATUS_SUCCESS,
            'paid_at' => now(),
            'verified_via_webhook' => $viaWebhook,
        ]);

        PaymentLog::create([
            'hospital_id' => $payment->hospital_id,
            'payment_id' => $payment->id,
            'event' => $viaWebhook ? 'webhook' : 'verify',
            'payload' => ['reference' => $payment->reference],
            'result' => 'paid',
        ]);

        $appointment = $payment->appointment;
        $appointment->update([
            'payment_status' => Appointment::PAY_PAID,
            'payment_id' => $payment->id,
        ]);

        $this->notices->send(
            $appointment->patient->user, $appointment->hospital_id, 'payment_success',
            'Payment successful',
            "Receipt ref {$payment->reference}. Show this at check-in or proceed straight to the queue.",
            "/patient/appointments/{$appointment->id}"
        );

        AuditLog::record($appointment->hospital_id, null, 'payment_paid', $payment,
            ['status' => Payment::STATUS_PENDING], ['status' => Payment::STATUS_SUCCESS]);
    }
}
