<?php

namespace App\Models;

use App\Concerns\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Per-hospital key-value settings (admin-configurable).
 * Cached per hospital; flushed on write.
 */
class Setting extends Model
{
    use BelongsToHospital;

    public const SESSION_LIFETIME_STAFF = 'session_lifetime_staff';

    public const SESSION_LIFETIME_PATIENT = 'session_lifetime_patient';

    public const QUEUE_LOW_THRESHOLD = 'queue_low_threshold';

    public const BOOKING_WINDOW_DAYS = 'booking_window_days';

    public const BOOKING_CUTOFF_MINUTES = 'booking_cutoff_minutes';

    protected $fillable = ['hospital_id', 'key', 'value'];

    public static function get(int $hospitalId, string $key, ?string $default = null): ?string
    {
        return Cache::remember("settings.{$hospitalId}.{$key}", 3600, function () use ($hospitalId, $key, $default): ?string {
            return static::forHospital($hospitalId)->where('key', $key)->value('value') ?? $default;
        });
    }

    public static function set(int $hospitalId, string $key, ?string $value): void
    {
        static::updateOrCreate(
            ['hospital_id' => $hospitalId, 'key' => $key],
            ['value' => $value]
        );

        Cache::forget("settings.{$hospitalId}.{$key}");
    }
}
