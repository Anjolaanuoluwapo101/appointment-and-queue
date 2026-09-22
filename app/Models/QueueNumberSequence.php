<?php

namespace App\Models;

use App\Concerns\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;

/**
 * Daily counter per department backing queue numbers (PRD §9).
 * Reset is implicit: one row per department per day.
 */
class QueueNumberSequence extends Model
{
    use BelongsToHospital;

    protected $fillable = [
        'hospital_id',
        'department_id',
        'queue_date',
        'last_number',
    ];

    protected $casts = [
        'queue_date' => 'date',
        'last_number' => 'integer',
    ];

    protected $attributes = [
        'last_number' => 0,
    ];
}
