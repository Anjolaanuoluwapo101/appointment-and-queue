<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\QueueEntry;
use App\Models\User;
use App\Services\QueueService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Stages (or removes) a camera-ready demo queue scene for filming.
 *
 * Creates clearly-marked demo patients (0999-prefixed phones, no login
 * accounts so no emails go out) and drives them through the real
 * QueueService into waiting / called / in-consultation / completed
 * states. Only ever touches rows it created; use --clean afterwards
 * to remove every trace.
 */
#[Signature('demo:scene {--email= : practitioner email to stage the scene for} {--clean : remove the demo scene instead of staging it}')]
#[Description('Stage or remove a demo queue scene for a practitioner')]
class DemoScene extends Command
{
    private const DEMO_PHONE_PREFIX = '0999';

    private const SCENE = [
        ['name' => 'Demo Patient One', 'state' => 'waiting'],
        ['name' => 'Demo Patient Two', 'state' => 'waiting'],
        ['name' => 'Demo Patient Three', 'state' => 'waiting'],
        ['name' => 'Demo Patient Four', 'state' => 'called'],
        ['name' => 'Demo Patient Five', 'state' => 'in_consultation'],
        ['name' => 'Demo Patient Six', 'state' => 'completed'],
    ];

    public function handle(QueueService $queue): int
    {
        $email = (string) $this->option('email');

        if ($email === '') {
            $this->error('Pass the practitioner explicitly, e.g. --email=dr.grace@lagoonhealth.com');

            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();

        if ($user === null || $user->practitioner === null) {
            $this->error("No practitioner found for {$email}.");

            return self::FAILURE;
        }

        $practitioner = $user->practitioner;
        $department = $practitioner->departments()->orderBy('departments.id')->first();

        if ($department === null) {
            $this->error("Practitioner {$practitioner->full_name} has no department.");

            return self::FAILURE;
        }

        if ($this->option('clean')) {
            return $this->clean($department->hospital_id);
        }

        $actor = User::where('hospital_id', $department->hospital_id)
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_RECEPTIONIST])
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        if ($actor === null) {
            $this->error('No active admin or receptionist to act as check-in staff.');

            return self::FAILURE;
        }

        // Re-stage cleanly: remove any previous scene first.
        $this->clean($department->hospital_id, quiet: true);

        $minutesAgo = [52, 44, 36, 28, 18, 9];

        foreach (self::SCENE as $i => $spot) {
            $entry = $queue->walkIn(
                $department,
                $practitioner->id,
                ['full_name' => $spot['name'], 'phone' => self::DEMO_PHONE_PREFIX.'00000'.($i + 1)],
                'DEMO-'.($i + 1),
                'cash',
                $actor
            );

            $entry = match ($spot['state']) {
                'called' => $queue->call($entry, $actor),
                'in_consultation' => $queue->beginConsultation($queue->call($entry, $actor), $actor),
                'completed' => $queue->complete($queue->beginConsultation($queue->call($entry, $actor), $actor), $actor),
                default => $entry,
            };

            // Backdate so waiting times look alive on camera.
            $created = now()->subMinutes($minutesAgo[$i]);
            $entry->update(['created_at' => $created, 'updated_at' => $created]);
            $entry->appointment?->update(['created_at' => $created, 'scheduled_at' => $created]);

            $this->line("  {$entry->queue_number}  {$spot['name']}  → {$entry->status}");
        }

        $this->info("Scene staged for {$practitioner->full_name} ({$department->name}).");
        $this->line("Film at: /staff/my-queue (as {$email}) and /display/queue/{$department->id}");

        return self::SUCCESS;
    }

    private function clean(int $hospitalId, bool $quiet = false): int
    {
        $patientIds = Patient::where('hospital_id', $hospitalId)
            ->where('phone', 'like', self::DEMO_PHONE_PREFIX.'%')
            ->pluck('id');

        if ($patientIds->isEmpty()) {
            if (! $quiet) {
                $this->info('No demo scene to remove.');
            }

            return self::SUCCESS;
        }

        $appointmentIds = Appointment::whereIn('patient_id', $patientIds)->pluck('id');
        $entryIds = QueueEntry::whereIn('appointment_id', $appointmentIds)->pluck('id');

        QueueEntry::whereIn('id', $entryIds)->delete();
        Payment::whereIn('appointment_id', $appointmentIds)->delete();
        Appointment::whereIn('id', $appointmentIds)->delete();
        AuditLog::where(function ($query) use ($appointmentIds, $entryIds): void {
            $query->where(function ($query) use ($appointmentIds): void {
                $query->where('subject_type', Appointment::class)->whereIn('subject_id', $appointmentIds);
            })->orWhere(function ($query) use ($entryIds): void {
                $query->where('subject_type', QueueEntry::class)->whereIn('subject_id', $entryIds);
            });
        })->delete();
        Patient::whereIn('id', $patientIds)->delete();

        if (! $quiet) {
            $this->info("Removed demo scene ({$patientIds->count()} patients).");
        }

        return self::SUCCESS;
    }
}
