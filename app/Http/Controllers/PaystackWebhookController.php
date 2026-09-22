<?php

namespace App\Http\Controllers;

use App\Models\Hospital;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Paystack webhook receiver (PRD §12 online flow). CSRF-exempt, signature
 * verified, idempotent on provider reference.
 */
class PaystackWebhookController extends Controller
{
    public function handle(Request $request, PaymentService $payments): Response
    {
        $payload = $request->getContent();
        $signature = (string) $request->header('x-paystack-signature', '');

        $data = json_decode($payload, true);

        if (! is_array($data)) {
            return response('Invalid payload.', 400);
        }

        $reference = $data['data']['reference'] ?? null;
        $payment = is_string($reference) ? Payment::where('reference', $reference)->first() : null;

        $hospital = $payment?->appointment?->hospital
            ?? Hospital::where('is_active', true)->orderBy('id')->first();

        if ($hospital === null || ! $payments->verifyWebhookSignature($payload, $signature, $hospital)) {
            return response('Invalid signature.', 401);
        }

        $result = $payments->handleWebhook($data);

        return response($result, 200);
    }
}
