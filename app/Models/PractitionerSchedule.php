<?php

namespace App\Models;

use App\Concerns\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Weekly working pattern for a practitioner in one department (PRD §8).
 *
 * @property int $id
 * @property int $practitioner_id
 * @property int $department_id
 * @property int $weekday 0 (Sunday) – 6 (Saturday)
 */
class PractitionerSchedule extends Model
{
    use BelongsToHospital;

    protected $fillable = [
        'hospital_id',
        'practitioner_id',
        'department_id',
        'weekday',
        'start_time',
        'end_time',
        'slot_duration_minutes',
        'max_per_slot',
        'break_start',
        'break_end',
        'is_active',
    ];

    protected $casts = [
        'weekday' => 'integer',
        'slot_duration_minutes' => 'integer',
        'max_per_slot' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'max_per_slot' => 1,
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

    /** @return HasMany<AppointmentSlot, $this> */
    public function slots(): HasMany
    {
        return $this->hasMany(AppointmentSlot::class, 'schedule_id');
    }
}
