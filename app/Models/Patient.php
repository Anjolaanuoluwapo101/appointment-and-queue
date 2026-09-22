<?php

namespace App\Models;

use App\Concerns\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $hospital_id
 * @property string $full_name
 * @property string $phone
 */
class Patient extends Model
{
    use BelongsToHospital;

    protected $fillable = [
        'hospital_id',
        'user_id',
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

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
