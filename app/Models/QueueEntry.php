<?php

namespace App\Models;

use App\Concerns\BelongsToHospital;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One patient's place in a department's daily queue (PRD §9).
 * Cancelled entries stay visible to queue viewers; the appointment
 * record is untouched (queue-only removal decision).
 */
class QueueEntry extends Model
{
    use BelongsToHospital;

    public const STATUS_WAITING = 'waiting';

    public const STATUS_CALLED = 'called';

    public const STATUS_IN_CONSULTATION = 'in_consultation';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_SKIPPED = 'skipped';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'hospital_id',
        'department_id',
        'practitioner_id',
        'patient_id',
        'appointment_id',
        'queue_number',
        'queue_date',
        'status',
        'is_recalled',
        'cancel_reason',
        'called_at',
        'started_consultation_at',
        'completed_at',
        'action_by',
    ];

    protected $casts = [
        'queue_date' => 'date',
        'is_recalled' => 'boolean',
        'called_at' => 'datetime',
        'started_consultation_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => self::STATUS_WAITING,
        'is_recalled' => false,
    ];

    /** @param Builder<static> $query */
    public function scopeActive(Builder $query): void
    {
        $query->whereIn('status', [self::STATUS_WAITING, self::STATUS_CALLED, self::STATUS_SKIPPED]);
    }

    /** @param Builder<static> $query */
    public function scopeToday(Builder $query, string $date): void
    {
        $query->whereDate('queue_date', $date);
    }

    /** @return BelongsTo<Department, $this> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** @return BelongsTo<Practitioner, $this> */
    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(Practitioner::class);
    }

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /** @return BelongsTo<Appointment, $this> */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_WAITING, self::STATUS_CALLED, self::STATUS_SKIPPED], true);
    }
}
