<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $timezone
 */
class Hospital extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'timezone',
        'paystack_secret',
        'paystack_public_key',
        'is_active',
    ];

    protected $casts = [
        'paystack_secret' => 'encrypted',
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    /** @return HasMany<Department, $this> */
    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    /** @return HasMany<Practitioner, $this> */
    public function practitioners(): HasMany
    {
        return $this->hasMany(Practitioner::class);
    }

    /** @return HasMany<Patient, $this> */
    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class);
    }
}
