<?php

namespace App\Models;

use App\Concerns\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Appointment with orthogonal payment tracking (PRD §7).
 */
class Appointment extends Model
{
    use BelongsToHospital;

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_CHECKED_IN = 'checked_in';

    public const STATUS_PENDING_CLEARANCE = 'pending_clearance';

    public const STATUS_CLEARED = 'cleared';

    public const STATUS_IN_QUEUE = 'in_queue';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_NO_SHOW = 'no_show';

    public const PAY_MODE_ONLINE = 'online';

    public const PAY_MODE_PHYSICAL = 'physical';

    public const PAY_UNPAID = 'unpaid';

    public const PAY_PENDING = 'pending';

    public const PAY_PAID = 'paid';

    public const PAY_FAILED = 'failed';

    public const PAY_REFUNDED = 'refunded';

    public const PAY_WAIVED = 'waived';

    protected $fillable = [
        'hospital_id',
        'patient_id',
        'practitioner_id',
        'department_id',
        'slot_id',
        'scheduled_at',
        'status',
        'payment_mode',
        'payment_status',
        'payment_id',
        'is_walk_in',
        'cancellation_reason',
        'reschedule_count',
        'checked_in_by',
        'checked_in_at',
        'attendance_confirmed_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'is_walk_in' => 'boolean',
        'reschedule_count' => 'integer',
        'checked_in_at' => 'datetime',
        'attendance_confirmed_at' => 'datetime',
    ];

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /** @return BelongsTo<Practitioner, $this> */
    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(Practitioner::class);
    }

    /** @return BelongsTo<Department, $this> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** @return BelongsTo<AppointmentSlot, $this> */
    public function slot(): BelongsTo
    {
        return $this->belongsTo(AppointmentSlot::class, 'slot_id');
    }

    /** @return BelongsTo<Payment, $this> */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, [self::STATUS_SCHEDULED, self::STATUS_PENDING_CLEARANCE], true);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === self::PAY_PAID;
    }
}
