<?php

namespace App\Models;

use App\Concerns\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * User-facing inbox entry (PRD §14, §20). Email delivery is tracked
 * separately in notification_logs with its delivery status.
 */
class Notification extends Model
{
    use BelongsToHospital;

    protected $fillable = [
        'hospital_id',
        'user_id',
        'type',
        'title',
        'body',
        'link',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markRead(): void
    {
        $this->update(['read_at' => now()]);
    }
}
