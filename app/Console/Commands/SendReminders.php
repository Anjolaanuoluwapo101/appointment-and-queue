<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Appointment reminders (PRD §14 MVP): day-before for tomorrow's
 * bookings, day-of for slots starting in ~2 hours. Runs every 15
 * minutes; sends are idempotent per appointment per day.
 */
#[Signature('reminders:send')]
#[Description('Send appointment reminders (day-before and 2-hours-before)')]
class SendReminders extends Command
{
    public function handle(NotificationService $notices): int
    {
        $now = Carbon::now();
        $dayBefore = 0;
        $dayOf = 0;

        $tomorrow = Appointment::where('status', Appointment::STATUS_SCHEDULED)
            ->whereDate('scheduled_at', $now->copy()->addDay()->toDateString())
            ->with(['patient.user', 'department:id,name'])
            ->get();

        foreach ($tomorrow as $appointment) {
            $sent = $notices->sendUnique(
                $appointment->patient->user,
                $appointment->hospital_id,
                'appointment_reminder_day_before',
                'Appointment tomorrow',
                "{$appointment->department->name} on {$appointment->scheduled_at->format('D d M H:i')}.",
                "/patient/appointments/{$appointment->id}"
            );

            if ($sent !== null) {
                $dayBefore++;
            }
        }

        $upcoming = Appointment::where('status', Appointment::STATUS_SCHEDULED)
            ->whereBetween('scheduled_at', [$now->copy()->addMinutes(105), $now->copy()->addMinutes(135)])
            ->with(['patient.user', 'department:id,name'])
            ->get();

        foreach ($upcoming as $appointment) {
            $sent = $notices->sendUnique(
                $appointment->patient->user,
                $appointment->hospital_id,
                'appointment_reminder_day_of',
                'Appointment in 2 hours',
                "{$appointment->department->name} at {$appointment->scheduled_at->format('H:i')}. Please arrive early.",
                "/patient/appointments/{$appointment->id}"
            );

            if ($sent !== null) {
                $dayOf++;
            }
        }

        $this->info("Reminders: {$dayBefore} day-before, {$dayOf} day-of.");

        return self::SUCCESS;
    }
}
