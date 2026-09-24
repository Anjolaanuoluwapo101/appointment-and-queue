<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\Hospital;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\URL;

/**
 * Appointment reminders (PRD §14 MVP): day-before for tomorrow's
 * bookings, day-of for slots starting in ~2 hours, plus the attendance
 * nudge for tomorrow's unconfirmed bookings. Runs every 15 minutes;
 * sends are idempotent per appointment per day.
 */
#[Signature('reminders:send')]
#[Description('Send appointment reminders (day-before and 2-hours-before)')]
class SendReminders extends Command
{
    public function handle(NotificationService $notices): int
    {
        $dayBefore = 0;
        $dayOf = 0;
        $nudges = 0;

        foreach (Hospital::where('is_active', true)->get() as $hospital) {
            $now = Carbon::now($hospital->timezone ?? 'Africa/Lagos');

            $tomorrow = Appointment::forHospital($hospital->id)
                ->where('status', Appointment::STATUS_SCHEDULED)
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

            $upcoming = Appointment::forHospital($hospital->id)
                ->where('status', Appointment::STATUS_SCHEDULED)
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

            // Attendance nudge: tomorrow's bookings the patient hasn't
            // confirmed yet. The signed one-click link is generated with a
            // day-truncated expiry so the stored URL is byte-identical
            // across runs and sendUnique() dedupes correctly.
            $unconfirmed = Appointment::forHospital($hospital->id)
                ->where('status', Appointment::STATUS_SCHEDULED)
                ->whereNull('attendance_confirmed_at')
                ->whereDate('scheduled_at', $now->copy()->addDay()->toDateString())
                ->with(['patient.user', 'department:id,name'])
                ->get();

            foreach ($unconfirmed as $appointment) {
                $confirmUrl = URL::signedRoute(
                    'patient.appointments.confirm.link',
                    ['appointment' => $appointment->id],
                    $now->copy()->startOfDay()->addDays(8)
                );

                $sent = $notices->sendUnique(
                    $appointment->patient->user,
                    $appointment->hospital_id,
                    'attendance_nudge',
                    'Will you attend tomorrow?',
                    "Tap to confirm your {$appointment->department->name} appointment on {$appointment->scheduled_at->format('D d M H:i')}: {$confirmUrl}",
                    $confirmUrl
                );

                if ($sent !== null) {
                    $nudges++;
                }
            }
        }

        $this->info("Reminders: {$dayBefore} day-before, {$dayOf} day-of, {$nudges} nudges.");

        return self::SUCCESS;
    }
}
