<?php

namespace App\Models;

use App\Concerns\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Delivery record for every outbound notice: what was sent, to whom,
 * over which channel, and whether it landed (PRD §20).
 */
class NotificationLog extends Model
{
    use BelongsToHospital;

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'hospital_id',
        'notification_id',
        'channel',
        'recipient',
        'subject',
        'status',
        'error',
    ];

    /** @return BelongsTo<Notification, $this> */
    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }
}
