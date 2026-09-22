<?php

namespace App\Models;

use App\Concerns\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One collection attempt per appointment: Paystack online or manual
 * physical. Unique provider reference gives webhook idempotency.
 */
class Payment extends Model
{
    use BelongsToHospital;

    public const PROVIDER_PAYSTACK = 'paystack';

    public const PROVIDER_MANUAL = 'manual';

    public const STATUS_PENDING = 'pending';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    public const STATUS_ABANDONED = 'abandoned';

    public const STATUS_REFUNDED = 'refunded';

    protected $fillable = [
        'hospital_id',
        'appointment_id',
        'patient_id',
        'provider',
        'reference',
        'amount_kobo',
        'status',
        'paid_at',
        'verified_via_webhook',
        'receipt_no',
        'method',
        'metadata',
    ];

    protected $casts = [
        'amount_kobo' => 'integer',
        'paid_at' => 'datetime',
        'verified_via_webhook' => 'boolean',
        'metadata' => 'array',
    ];

    /** @return BelongsTo<Appointment, $this> */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }
}
