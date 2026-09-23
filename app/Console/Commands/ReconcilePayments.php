<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Re-queries Paystack for payments stuck in pending (>30min) and settles
 * them to paid, failed, or abandoned (PRD §12 online flow).
 */
#[Signature('payments:reconcile')]
#[Description('Reconcile stale pending payments against Paystack')]
class ReconcilePayments extends Command
{
    public function handle(PaymentService $payments): int
    {
        $stale = Payment::where('provider', Payment::PROVIDER_PAYSTACK)
            ->where('status', Payment::STATUS_PENDING)
            ->where('created_at', '<', now()->subMinutes(30))
            ->get();

        $counts = ['paid' => 0, 'failed' => 0, 'abandoned' => 0];

        foreach ($stale as $payment) {
            try {
                if ($payments->verifyByReference($payment)) {
                    $counts['paid']++;

                    continue;
                }
            } catch (\Throwable $e) {
                // Skip updating status on temporary HTTP/API network errors
                continue;
            }

            $payment->update(['status' => Payment::STATUS_ABANDONED]);
            $payment->appointment?->update(['payment_status' => Appointment::PAY_FAILED]);
            $counts['abandoned']++;
        }

        $this->info("Reconciled: {$counts['paid']} paid, {$counts['abandoned']} abandoned.");

        return self::SUCCESS;
    }
}
