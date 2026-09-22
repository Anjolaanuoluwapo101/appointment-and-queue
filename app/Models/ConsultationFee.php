<?php

namespace App\Models;

use App\Concerns\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Consultation fee: department base (practitioner_id null) with optional
 * per-practitioner override. Amounts in kobo; server is source of truth.
 */
class ConsultationFee extends Model
{
    use BelongsToHospital;

    protected $fillable = [
        'hospital_id',
        'department_id',
        'practitioner_id',
        'amount_kobo',
        'currency',
    ];

    protected $casts = [
        'amount_kobo' => 'integer',
    ];

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
}
