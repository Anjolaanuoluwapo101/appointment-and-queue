<?php

namespace App\Models;

use App\Concerns\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Date-specific override: full day off or adjusted hours (PRD §8).
 */
class ScheduleException extends Model
{
    use BelongsToHospital;

    public const TYPE_DAY_OFF = 'day_off';

    public const TYPE_ADJUSTED = 'adjusted';

    protected $fillable = [
        'hospital_id',
        'practitioner_id',
        'department_id',
        'date',
        'type',
        'start_time',
        'end_time',
        'reason',
    ];

    protected $casts = [
        'date' => 'date',
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

    public function isDayOff(): bool
    {
        return $this->type === self::TYPE_DAY_OFF;
    }
}
