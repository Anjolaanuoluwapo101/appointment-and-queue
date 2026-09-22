<?php

namespace App\Models;

use App\Concerns\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Raw Paystack init/verify/webhook/refund payloads for audit and disputes.
 */
class PaymentLog extends Model
{
    use BelongsToHospital;

    protected $fillable = [
        'hospital_id',
        'payment_id',
        'event',
        'payload',
        'result',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    /** @return BelongsTo<Payment, $this> */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
