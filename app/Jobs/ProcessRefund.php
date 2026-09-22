<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\PaymentLog;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\PaymentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Auto-refund on cancellation (PRD §13). Retried 3x; final failure is
 * logged with the reference for manual admin resolution (refund inbox
 * surfaces it in Phase 4 dashboards).
 */
class ProcessRefund implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $appointmentId) {}

    public function handle(PaymentService $payments, NotificationService $notices): void
    {
        $appointment = Appointment::find($this->appointmentId);

        if ($appointment === null || ! $appointment->isPaid()) {
            return;
        }

        $payment = $appointment->payment;

        if ($payment === null || ! $payment->isSuccessful()) {
            return;
        }

        if ($payments->refund($payment)) {
            $payment->update(['status' => Payment::STATUS_REFUNDED]);
            $appointment->update(['payment_status' => Appointment::PAY_REFUNDED]);

            $notices->send(
                $appointment->patient->user, $appointment->hospital_id, 'payment_refunded',
                'Payment refunded',
                "Ref {$payment->reference} was refunded automatically after cancellation.",
                "/patient/appointments/{$appointment->id}"
            );

            AuditLog::record($appointment->hospital_id, null, 'payment_refunded', $payment,
                ['status' => Payment::STATUS_SUCCESS], ['status' => Payment::STATUS_REFUNDED]);

            return;
        }

        throw new \RuntimeException("Paystack refund failed for reference {$payment->reference}.");
    }

    public function failed(?\Throwable $exception): void
    {
        $appointment = Appointment::find($this->appointmentId);

        if ($appointment === null) {
            Log::error("Auto-refund failed after retries for missing appointment {$this->appointmentId}.");

            return;
        }

        $reference = $appointment->payment?->reference ?? 'unknown';

        PaymentLog::create([
            'hospital_id' => $appointment->hospital_id,
            'payment_id' => $appointment->payment_id,
            'event' => 'refund_failed',
            'payload' => ['reference' => $reference, 'error' => $exception?->getMessage()],
            'result' => 'needs_manual_resolution',
        ]);

        app(NotificationService::class)->sendToStaff(
            $appointment->hospital_id,
            [User::ROLE_ADMIN],
            'refund_failed',
            'Auto-refund failed',
            "Reference {$reference} needs manual resolution.",
            '/admin/departments'
        );

        Log::error("Auto-refund failed after retries. Reference {$reference} needs manual resolution.");
    }
}
