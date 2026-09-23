<?php

namespace App\Services;

use App\Models\AppointmentSlot;
use App\Models\Hospital;
use App\Models\PractitionerSchedule;
use App\Models\ScheduleException;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Materialises bookable slots from weekly schedules (PRD §8).
 * Idempotent: existing slots are left untouched.
 */
class SlotGenerator
{
    public function generateForHospital(Hospital $hospital, ?int $daysAhead = null): int
    {
        $days = $daysAhead ?? (int) (Setting::get($hospital->id, Setting::BOOKING_WINDOW_DAYS) ?? 30);
        $today = Carbon::now($hospital->timezone ?? 'Africa/Lagos')->startOfDay();
        $created = 0;

        $schedules = PractitionerSchedule::forHospital($hospital->id)
            ->where('is_active', true)
            ->with(['practitioner', 'department'])
            ->get();

        foreach ($schedules as $schedule) {
            if (! $schedule->practitioner?->isBookable() || ! $schedule->department?->is_active) {
                continue;
            }

            for ($offset = 0; $offset <= $days; $offset++) {
                $date = $today->copy()->addDays($offset);

                if ((int) $date->format('w') !== (int) $schedule->weekday) {
                    continue;
                }

                $created += $this->generateForDate($hospital, $schedule, $date);
            }
        }

        return $created;
    }

    private function generateForDate(Hospital $hospital, PractitionerSchedule $schedule, Carbon $date): int
    {
        $exception = ScheduleException::forHospital($hospital->id)
            ->where('practitioner_id', $schedule->practitioner_id)
            ->where(function ($query) use ($schedule): void {
                $query->where('department_id', $schedule->department_id)->orWhereNull('department_id');
            })
            ->whereDate('date', $date->toDateString())
            ->first();

        if ($exception !== null && $exception->isDayOff()) {
            return 0;
        }

        $start = $exception?->start_time ?? $schedule->start_time;
        $end = $exception?->end_time ?? $schedule->end_time;
        $chunks = $this->sliceDay($date, $start, $end, $schedule);
        $created = 0;

        foreach ($chunks as [$startsAt, $endsAt]) {
            $slot = AppointmentSlot::firstOrCreate(
                [
                    'practitioner_id' => $schedule->practitioner_id,
                    'department_id' => $schedule->department_id,
                    'starts_at' => $startsAt,
                ],
                [
                    'hospital_id' => $hospital->id,
                    'schedule_id' => $schedule->id,
                    'date' => $date->toDateString(),
                    'ends_at' => $endsAt,
                    'capacity' => $schedule->max_per_slot,
                ]
            );

            if ($slot->wasRecentlyCreated) {
                $created++;
            } elseif ($slot->capacity !== $schedule->max_per_slot && $slot->booked_count <= $schedule->max_per_slot) {
                $slot->update(['capacity' => $schedule->max_per_slot]);
            }
        }

        return $created;
    }

    /**
     * @return Collection<int, array{Carbon, Carbon}>
     */
    private function sliceDay(Carbon $date, string $start, string $end, PractitionerSchedule $schedule): Collection
    {
        $cursor = $date->copy()->setTimeFromTimeString($start);
        $limit = $date->copy()->setTimeFromTimeString($end);
        $duration = max(1, (int) $schedule->slot_duration_minutes);
        $chunks = collect();

        $breakStart = $schedule->break_start !== null ? $date->copy()->setTimeFromTimeString($schedule->break_start) : null;
        $breakEnd = $schedule->break_end !== null ? $date->copy()->setTimeFromTimeString($schedule->break_end) : null;

        while ($cursor->copy()->addMinutes($duration) <= $limit) {
            $chunkEnd = $cursor->copy()->addMinutes($duration);

            $overlapsBreak = $breakStart !== null && $breakEnd !== null
                && $cursor->lt($breakEnd) && $chunkEnd->gt($breakStart);

            if (! $overlapsBreak) {
                $chunks->push([$cursor->copy(), $chunkEnd]);
            }

            $cursor = $chunkEnd;
        }

        return $chunks;
    }
}
