<?php

namespace App\Models;

use App\Concerns\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $hospital_id
 * @property int|null $user_id
 * @property string|null $patient_number
 * @property string $full_name
 * @property string $phone
 */
class Patient extends Model
{
    use BelongsToHospital;

    protected $fillable = [
        'hospital_id',
        'user_id',
        'patient_number',
        'full_name',
        'phone',
        'email',
        'date_of_birth',
        'gender',
        'address',
        'secondary_contact_name',
        'secondary_contact_phone',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (Patient $patient) {
            if (empty($patient->patient_number) && ! empty($patient->hospital_id)) {
                $patient->patient_number = static::generateNextHospitalNumber($patient->hospital_id);
            }
        });
    }

    public static function generateNextHospitalNumber(int $hospitalId): string
    {
        $prefix = 'PAT-'.date('Y').'-';
        $latest = static::where('hospital_id', $hospitalId)
            ->where('patient_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('patient_number');

        $sequence = 1;
        if ($latest && preg_match('/-(\d+)$/', $latest, $matches)) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return sprintf('%s%05d', $prefix, $sequence);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
