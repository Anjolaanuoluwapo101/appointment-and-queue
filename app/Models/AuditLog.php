<?php

namespace App\Models;

use App\Concerns\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Append-only trail of important actions with before/after values
 * (PRD §19). Read access is admin-only.
 */
class AuditLog extends Model
{
    use BelongsToHospital;

    protected $fillable = [
        'hospital_id',
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'before',
        'after',
    ];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public static function record(
        int $hospitalId,
        ?int $userId,
        string $action,
        ?Model $subject = null,
        ?array $before = null,
        ?array $after = null,
    ): self {
        return static::create([
            'hospital_id' => $hospitalId,
            'user_id' => $userId,
            'action' => $action,
            'subject_type' => $subject !== null ? $subject->getMorphClass() : null,
            'subject_id' => $subject?->getKey(),
            'before' => $before,
            'after' => $after,
        ]);
    }
}
