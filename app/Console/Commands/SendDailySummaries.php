<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\Hospital;
use App\Services\NotificationService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Morning schedule summary per practitioner with a login account
 * (PRD §14 MVP): today's booking count. Runs daily at 06:00.
 */
#[Signature('summaries:send')]
#[Description('Send practitioners their daily schedule summary')]
class SendDailySummaries extends Command
{
    public function handle(NotificationService $notices): int
    {
        $sent = 0;

        foreach (Hospital::where('is_active', true)->get() as $hospital) {
            foreach ($hospital->practitioners()->with('user')->get() as $practitioner) {
                if ($practitioner->user === null) {
                    continue;
                }

                $count = Appointment::forHospital($hospital->id)
                    ->where('practitioner_id', $practitioner->id)
                    ->whereDate('scheduled_at', today()->toDateString())
                    ->where('status', Appointment::STATUS_SCHEDULED)
                    ->count();

                $notices->send(
                    $practitioner->user,
                    $hospital->id,
                    'daily_schedule_summary',
                    "Today's schedule: {$count} appointment(s)",
                    $count > 0 ? 'Your first patient details are on your queue board.' : 'No bookings today.',
                    '/staff/my-queue'
                );
                $sent++;
            }
        }

        $this->info("Summaries sent to {$sent} practitioner(s).");

        return self::SUCCESS;
    }
}
