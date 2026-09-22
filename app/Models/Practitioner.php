<?php

namespace App\Models;

use App\Concerns\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Covers any healthcare worker who sees patients — doctors, nurses,
 * physiotherapists, pharmacists, lab scientists, etc. Never "Doctor".
 *
 * @property int $id
 * @property int $hospital_id
 * @property string $full_name
 * @property string $specialisation
 * @property string $availability
 */
class Practitioner extends Model
{
    use BelongsToHospital;

    public const AVAILABILITY_ACTIVE = 'active';

    public const AVAILABILITY_ON_LEAVE = 'on_leave';

    public const AVAILABILITY_UNAVAILABLE = 'unavailable';

    protected $fillable = [
        'hospital_id',
        'user_id',
        'full_name',
        'specialisation',
        'photo_path',
        'qualifications',
        'bio',
        'internal_contact',
        'availability',
    ];

    protected $attributes = [
        'availability' => self::AVAILABILITY_ACTIVE,
    ];

    /** @return BelongsToMany<Department> */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'practitioner_departments')->withTimestamps();
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isBookable(): bool
    {
        return $this->availability === self::AVAILABILITY_ACTIVE;
    }
}
