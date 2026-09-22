<?php

namespace App\Models;

use App\Concerns\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property int $hospital_id
 * @property string $name
 * @property string $queue_prefix
 * @property string $payment_mode
 * @property int $base_fee_kobo
 * @property bool $is_active
 */
class Department extends Model
{
    use BelongsToHospital;

    public const PAYMENT_ALLOW_BOTH = 'allow_both';

    public const PAYMENT_PHYSICAL_ONLY = 'physical_only';

    public const PAYMENT_ONLINE_REQUIRED = 'online_required';

    protected $fillable = [
        'hospital_id',
        'name',
        'queue_prefix',
        'room_label',
        'payment_mode',
        'base_fee_kobo',
        'is_active',
    ];

    protected $casts = [
        'base_fee_kobo' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'payment_mode' => self::PAYMENT_ALLOW_BOTH,
        'base_fee_kobo' => 0,
        'is_active' => true,
    ];

    /** @return BelongsToMany<Practitioner> */
    public function practitioners(): BelongsToMany
    {
        return $this->belongsToMany(Practitioner::class, 'practitioner_departments')->withTimestamps();
    }
}
