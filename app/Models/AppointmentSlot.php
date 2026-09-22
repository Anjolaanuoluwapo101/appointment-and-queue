<?php

namespace App\Models;

use App\Concerns\BelongsToHospital;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A bookable time chunk generated from a schedule (PRD §8).
 * Full once booked_count reaches capacity.
 */
class AppointmentSlot extends Model
{
    use BelongsToHospital;

    protected $fillable = [
        'hospital_id',
        'practitioner_id',
        'department_id',
        'schedule_id',
        'date',
        'starts_at',
        'ends_at',
        'capacity',
        'booked_count',
        'is_active',
    ];

    protected $casts = [
        'date' => 'date',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'capacity' => 'integer',
        'booked_count' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'capacity' => 1,
        'booked_count' => 0,
        'is_active' => true,
    ];

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

    /** @param Builder<static> $query */
    public function scopeBookable(Builder $query): void
    {
        $query->where('is_active', true)->whereRaw('booked_count < capacity');
    }

    public function isFull(): bool
    {
        return $this->booked_count >= $this->capacity;
    }

    public function remaining(): int
    {
        return max(0, $this->capacity - $this->booked_count);
    }
}
