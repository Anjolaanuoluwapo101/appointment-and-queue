<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'hospital_id', 'role', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
/**
 * Auth user with hospital role (PRD §5): patient, receptionist,
 * practitioner, or admin. Staff act within their hospital.
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_PATIENT = 'patient';

    public const ROLE_RECEPTIONIST = 'receptionist';

    public const ROLE_PRACTITIONER = 'practitioner';

    public const ROLE_ADMIN = 'admin';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Hospital, $this> */
    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    /** @return HasOne<Patient, $this> */
    public function patient(): HasOne
    {
        return $this->hasOne(Patient::class);
    }

    /** @return HasOne<Staff, $this> */
    public function staff(): HasOne
    {
        return $this->hasOne(Staff::class);
    }

    /** @return HasOne<Practitioner, $this> */
    public function practitioner(): HasOne
    {
        return $this->hasOne(Practitioner::class);
    }

    public function isStaff(): bool
    {
        return in_array($this->role, [self::ROLE_RECEPTIONIST, self::ROLE_PRACTITIONER, self::ROLE_ADMIN], true);
    }
}
