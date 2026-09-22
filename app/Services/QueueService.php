<?php

namespace App\Services;

use App\Events\PatientQueueUpdated;
use App\Events\QueueUpdated;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\QueueEntry;
use App\Models\QueueNumberSequence;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Check-in gate and department queue operations (PRD §9, §12).
 * Queues are department-scoped with daily resetting numbers;
 * practitioners filter the shared queue to their patients.
 */
class QueueService
{
    public function __construct(private NotificationService $notices) {}

    /**
     * Receptionist check-in (PRD §12). Paid appointments auto-clear and
     * join the queue; unpaid ones park at Pending Clearance.
     *
     * @throws ValidationException
     */
    public function checkIn(Appointment $appointment, User $actor): Appointment
    {
        if ($appointment->status !== Appointment::STATUS_SCHEDULED) {
            throw ValidationException::withMessages(['appointment' => 'Only scheduled appointments can be checked in.']);
        }

        $fresh = DB::transaction(function () use ($appointment, $actor): Appointment {
            $appointment->update([
                'status' => Appointment::STATUS_CHECKED_IN,
                'checked_in_by' => $actor->id,
                'checked_in_at' => now(),
            ]);

            if ($appointment->isPaid() || $appointment->payment_status === Appointment::PAY_WAIVED) {
                $this->enqueue($appointment->fresh(), $actor);
            } else {
                $appointment->update(['status' => Appointment::STATUS_PENDING_CLEARANCE]);
            }

            return $appointment->fresh();
        });

        $patient = $fresh->patient;

        if ($fresh->status === Appointment::STATUS_IN_QUEUE) {
            $entry = QueueEntry::where('appointment_id', $fresh->id)->first();
            $this->notices->send(
                $patient->user, $fresh->hospital_id, 'checked_in',
                'Checked in — queue number assigned',
                "Your queue number is {$entry?->queue_number}. Track your position live.",
                "/patient/appointments/{$fresh->id}"
            );
        } else {
            $this->notices->send(
                $patient->user, $fresh->hospital_id, 'clearance_pending',
                'Payment clearance pending',
                'You are checked in. Please pay at the desk to join the queue.',
                "/patient/appointments/{$fresh->id}"
            );
        }

        AuditLog::record($fresh->hospital_id, $actor->id, 'checked_in', $fresh,
            ['status' => Appointment::STATUS_SCHEDULED], ['status' => $fresh->status]);

        return $fresh;
    }

    /**
     * Manual physical clearance: records the cash/transfer/POS collection
     * and joins the queue (PRD §12).
     *
     * @throws ValidationException
     */
    public function clearManually(Appointment $appointment, string $receiptNo, string $method, User $actor): Appointment
    {
        if ($appointment->status !== Appointment::STATUS_PENDING_CLEARANCE) {
            throw ValidationException::withMessages(['appointment' => 'Only appointments pending clearance can be cleared.']);
        }

        return DB::transaction(function () use ($appointment, $receiptNo, $method, $actor): Appointment {
            $payment = Payment::create([
                'hospital_id' => $appointment->hospital_id,
                'appointment_id' => $appointment->id,
                'patient_id' => $appointment->patient_id,
                'provider' => Payment::PROVIDER_MANUAL,
                'reference' => 'RCPT-'.$appointment->id.'-'.Str::upper(Str::random(8)),
                'amount_kobo' => app(FeeResolver::class)->resolve(
                    $appointment->hospital_id, $appointment->department_id, $appointment->practitioner_id
                ),
                'status' => Payment::STATUS_SUCCESS,
                'paid_at' => now(),
                'receipt_no' => $receiptNo,
                'method' => $method,
            ]);

            $appointment->update([
                'status' => Appointment::STATUS_CLEARED,
                'payment_status' => Appointment::PAY_PAID,
                'payment_id' => $payment->id,
            ]);

            $this->enqueue($appointment->fresh(), $actor);

            return $appointment->fresh();
        });

        $fresh = $appointment->fresh();
        $patient = $fresh->patient;
        $this->notices->send(
            $patient->user, $fresh->hospital_id, 'payment_cleared',
            'Payment cleared — now in queue',
            'Your payment was confirmed at the desk. Track your position live.',
            "/patient/appointments/{$fresh->id}"
        );
        AuditLog::record($fresh->hospital_id, $fresh->checked_in_by, 'payment_cleared_manual', $fresh,
            ['payment_status' => Appointment::PAY_UNPAID], ['payment_status' => Appointment::PAY_PAID]);

        return $fresh;
    }

    /**
     * Walk-in quick-add (PRD §12, physical-only MVP): find-or-create the
     * patient by phone, record the physical collection, join the queue.
     *
     * @param  array{full_name: string, phone: string}  $patientData
     *
     * @throws ValidationException
     */
    public function walkIn(
        Department $department,
        ?int $practitionerId,
        array $patientData,
        string $receiptNo,
        string $method,
        User $actor,
    ): QueueEntry {
        return tap(DB::transaction(function () use ($department, $practitionerId, $patientData, $receiptNo, $method, $actor): QueueEntry {
            $patient = Patient::firstOrCreate(
                ['hospital_id' => $department->hospital_id, 'phone' => $patientData['phone']],
                ['full_name' => $patientData['full_name']]
            );

            $appointment = Appointment::create([
                'hospital_id' => $department->hospital_id,
                'patient_id' => $patient->id,
                'practitioner_id' => $practitionerId,
                'department_id' => $department->id,
                'scheduled_at' => now(),
                'status' => Appointment::STATUS_PENDING_CLEARANCE,
                'payment_mode' => Appointment::PAY_MODE_PHYSICAL,
                'payment_status' => Appointment::PAY_UNPAID,
                'is_walk_in' => true,
                'checked_in_by' => $actor->id,
                'checked_in_at' => now(),
            ]);

            $this->clearManually($appointment->fresh(), $receiptNo, $method, $actor);

            return QueueEntry::where('appointment_id', $appointment->id)->firstOrFail();
        }), function (QueueEntry $entry) use ($actor): void {
            $patient = $entry->patient;
            $this->notices->send(
                $patient->user, $entry->hospital_id, 'walk_in_registered',
                'Walk-in registered',
                "You were queued as {$entry->queue_number}.",
                null
            );
            AuditLog::record($entry->hospital_id, $actor->id, 'walk_in_registered', $entry);
        });
    }

    /**
     * Calls the next waiting entry: recalled entries first, then FIFO.
     */
    public function callNext(Department $department, ?int $practitionerId, User $actor): ?QueueEntry
    {
        $entry = $this->orderedWaiting($department)
            ->when($practitionerId !== null, fn ($query) => $query->where('practitioner_id', $practitionerId))
            ->first();

        if ($entry === null) {
            return null;
        }

        $this->call($entry, $actor);

        return $entry->fresh();
    }

    /**
     * @throws ValidationException
     */
    public function call(QueueEntry $entry, User $actor): QueueEntry
    {
        if (! in_array($entry->status, [QueueEntry::STATUS_WAITING, QueueEntry::STATUS_SKIPPED], true)) {
            throw ValidationException::withMessages(['entry' => 'Only waiting entries can be called.']);
        }

        $entry->update([
            'status' => QueueEntry::STATUS_CALLED,
            'is_recalled' => false,
            'called_at' => now(),
            'action_by' => $actor->id,
        ]);

        $fresh = $entry->fresh();
        $room = $fresh->department->room_label;
        $patient = $fresh->patient;

        $this->notices->send(
            $patient->user, $fresh->hospital_id, 'called',
            "Called — {$fresh->queue_number}",
            'Please proceed'.($room !== null ? " to {$room}." : '.'),
            $fresh->appointment_id !== null ? "/patient/appointments/{$fresh->appointment_id}" : null
        );

        AuditLog::record($fresh->hospital_id, $actor->id, 'queue_called', $fresh,
            ['status' => QueueEntry::STATUS_WAITING], ['status' => QueueEntry::STATUS_CALLED]);

        $this->broadcast($fresh);
        $this->sendProgressiveNotices($fresh->department);

        return $fresh;
    }

    /**
     * @throws ValidationException
     */
    public function beginConsultation(QueueEntry $entry, User $actor): QueueEntry
    {
        if ($entry->status !== QueueEntry::STATUS_CALLED) {
            throw ValidationException::withMessages(['entry' => 'Only called entries can start consultation.']);
        }

        $entry->update([
            'status' => QueueEntry::STATUS_IN_CONSULTATION,
            'started_consultation_at' => now(),
            'action_by' => $actor->id,
        ]);

        AuditLog::record($entry->hospital_id, $actor->id, 'consultation_started', $entry,
            ['status' => QueueEntry::STATUS_CALLED], ['status' => QueueEntry::STATUS_IN_CONSULTATION]);

        $this->broadcast($entry->fresh());

        return $entry->fresh();
    }

    /**
     * Called but no response — stays visible for recall (PRD §9).
     *
     * @throws ValidationException
     */
    public function skip(QueueEntry $entry, User $actor): QueueEntry
    {
        if ($entry->status !== QueueEntry::STATUS_CALLED) {
            throw ValidationException::withMessages(['entry' => 'Only called entries can be skipped.']);
        }

        $entry->update(['status' => QueueEntry::STATUS_SKIPPED, 'action_by' => $actor->id]);

        AuditLog::record($entry->hospital_id, $actor->id, 'queue_skipped', $entry,
            ['status' => QueueEntry::STATUS_CALLED], ['status' => QueueEntry::STATUS_SKIPPED]);

        $this->broadcast($entry->fresh());
        $this->sendProgressiveNotices($entry->department);

        return $entry->fresh();
    }

    /**
     * Recalled entries rejoin at the next position (manual only, PRD §9).
     *
     * @throws ValidationException
     */
    public function recall(QueueEntry $entry, User $actor): QueueEntry
    {
        if ($entry->status !== QueueEntry::STATUS_SKIPPED) {
            throw ValidationException::withMessages(['entry' => 'Only skipped entries can be recalled.']);
        }

        $entry->update(['status' => QueueEntry::STATUS_WAITING, 'is_recalled' => true, 'action_by' => $actor->id]);

        AuditLog::record($entry->hospital_id, $actor->id, 'queue_recalled', $entry,
            ['status' => QueueEntry::STATUS_SKIPPED], ['status' => QueueEntry::STATUS_WAITING]);

        $this->broadcast($entry->fresh());

        return $entry->fresh();
    }

    /**
     * @throws ValidationException
     */
    public function complete(QueueEntry $entry, User $actor): QueueEntry
    {
        if (! in_array($entry->status, [QueueEntry::STATUS_CALLED, QueueEntry::STATUS_IN_CONSULTATION], true)) {
            throw ValidationException::withMessages(['entry' => 'Only called entries can be completed.']);
        }

        return tap(DB::transaction(function () use ($entry, $actor): QueueEntry {
            $entry->update([
                'status' => QueueEntry::STATUS_COMPLETED,
                'completed_at' => now(),
                'action_by' => $actor->id,
            ]);

            $entry->appointment?->update(['status' => Appointment::STATUS_COMPLETED]);

            $this->broadcast($entry->fresh());

            return $entry->fresh();
        }), function (QueueEntry $fresh): void {
            $patient = $fresh->patient;
            $this->notices->send(
                $patient->user, $fresh->hospital_id, 'consultation_completed',
                'Consultation completed',
                "Your visit ({$fresh->queue_number}) is marked complete.",
                $fresh->appointment_id !== null ? "/patient/appointments/{$fresh->appointment_id}" : null
            );
            AuditLog::record($fresh->hospital_id, $fresh->action_by, 'consultation_completed', $fresh,
                ['status' => QueueEntry::STATUS_IN_CONSULTATION], ['status' => QueueEntry::STATUS_COMPLETED]);

            $this->sendProgressiveNotices($fresh->department);
        });
    }

    /**
     * Queue-only removal (decision): the appointment record is untouched,
     * the entry stays visible to queue viewers.
     *
     * @throws ValidationException
     */
    public function cancelEntry(QueueEntry $entry, ?string $reason, User $actor): QueueEntry
    {
        if (! $entry->isActive()) {
            throw ValidationException::withMessages(['entry' => 'Only active entries can be cancelled.']);
        }

        $before = $entry->status;

        $entry->update([
            'status' => QueueEntry::STATUS_CANCELLED,
            'cancel_reason' => $reason,
            'action_by' => $actor->id,
        ]);

        $fresh = $entry->fresh();
        $this->notices->send(
            $fresh->patient->user, $fresh->hospital_id, 'queue_entry_cancelled',
            'Queue entry cancelled',
            "Your queue place ({$fresh->queue_number}) was cancelled by staff. Your appointment record is kept.",
            $fresh->appointment_id !== null ? "/patient/appointments/{$fresh->appointment_id}" : null
        );

        AuditLog::record($fresh->hospital_id, $actor->id, 'queue_entry_cancelled', $fresh,
            ['status' => $before], ['status' => QueueEntry::STATUS_CANCELLED]);

        $this->broadcast($fresh);
        $this->sendProgressiveNotices($fresh->department);

        return $fresh;
    }

    /**
     * Full department-day snapshot for dashboards and the display board.
     *
     * @return array<string, mixed>
     */
    public function snapshot(Department $department, ?string $date = null): array
    {
        $day = $date ?? Carbon::now($department->hospital->timezone ?? 'Africa/Lagos')->toDateString();

        $entries = QueueEntry::where('department_id', $department->id)
            ->whereDate('queue_date', $day)
            ->with(['patient:id,full_name', 'practitioner:id,full_name'])
            ->orderByDesc('is_recalled')
            ->orderBy('id')
            ->get();

        $serving = $entries
            ->whereIn('status', [QueueEntry::STATUS_CALLED, QueueEntry::STATUS_IN_CONSULTATION])
            ->sortByDesc('called_at')
            ->first();

        return [
            'department' => $department->only('id', 'name', 'room_label'),
            'date' => $day,
            'serving' => $serving?->only('id', 'queue_number', 'status'),
            'waiting' => $entries->where('status', QueueEntry::STATUS_WAITING)->values(),
            'skipped' => $entries->where('status', QueueEntry::STATUS_SKIPPED)->values(),
            'cancelled' => $entries->where('status', QueueEntry::STATUS_CANCELLED)->values(),
            'completed_count' => $entries->where('status', QueueEntry::STATUS_COMPLETED)->count(),
        ];
    }

    /**
     * Patient-facing position: serving number plus patients ahead.
     *
     * @return array<string, mixed>
     */
    public function positionFor(QueueEntry $entry): array
    {
        $snapshot = $this->snapshot($entry->department);
        $ordered = collect($snapshot['waiting'])->pluck('id');

        return [
            'queue_number' => $entry->queue_number,
            'status' => $entry->status,
            'serving' => $snapshot['serving']['queue_number'] ?? null,
            'patients_ahead' => max(0, $ordered->search($entry->id) === false ? 0 : $ordered->search($entry->id)),
            'room' => $snapshot['department']['room_label'],
        ];
    }

    private function enqueue(Appointment $appointment, ?User $actor): QueueEntry
    {
        return DB::transaction(function () use ($appointment, $actor): QueueEntry {
            $department = $appointment->department;
            $day = Carbon::now($department->hospital->timezone ?? 'Africa/Lagos')->toDateString();

            $sequence = QueueNumberSequence::lockForUpdate()
                ->where('hospital_id', $appointment->hospital_id)
                ->where('department_id', $appointment->department_id)
                ->whereDate('queue_date', $day)
                ->first()
                ?? QueueNumberSequence::create([
                    'hospital_id' => $appointment->hospital_id,
                    'department_id' => $appointment->department_id,
                    'queue_date' => $day,
                    'last_number' => 0,
                ]);
            $sequence->increment('last_number');

            $number = $department->queue_prefix.str_pad((string) $sequence->fresh()->last_number, 3, '0', STR_PAD_LEFT);

            $entry = QueueEntry::create([
                'hospital_id' => $appointment->hospital_id,
                'department_id' => $appointment->department_id,
                'practitioner_id' => $appointment->practitioner_id,
                'patient_id' => $appointment->patient_id,
                'appointment_id' => $appointment->id,
                'queue_number' => $number,
                'queue_date' => $day,
                'status' => QueueEntry::STATUS_WAITING,
                'action_by' => $actor?->id,
            ]);

            $appointment->update(['status' => Appointment::STATUS_IN_QUEUE]);

            $this->broadcast($entry);

            return $entry;
        });
    }

    /** @return Builder<QueueEntry> */
    private function orderedWaiting(Department $department)
    {
        $day = Carbon::now($department->hospital->timezone ?? 'Africa/Lagos')->toDateString();

        return QueueEntry::where('department_id', $department->id)
            ->whereDate('queue_date', $day)
            ->where('status', QueueEntry::STATUS_WAITING)
            ->orderByDesc('is_recalled')
            ->orderBy('id');
    }

    private function broadcast(QueueEntry $entry): void
    {
        $entry->loadMissing(['department', 'patient:id,full_name']);

        QueueUpdated::dispatch($entry->department_id);
        PatientQueueUpdated::dispatch($entry->id);
    }

    private function sendProgressiveNotices(Department $department): void
    {
        $snapshot = $this->snapshot($department);

        foreach ($snapshot['waiting'] as $index => $entry) {
            $threshold = match ($index) {
                5 => 5,
                3 => 3,
                1 => 1,
                default => null,
            };

            if ($threshold !== null) {
                $entry->loadMissing('patient.user');

                $this->notices->sendUnique(
                    $entry->patient->user,
                    $entry->hospital_id,
                    "queue_ahead_{$threshold}",
                    "Queue Update: {$threshold} patients ahead",
                    "There are now only {$threshold} patients ahead of you in the queue. Please get ready.",
                    $entry->appointment_id !== null ? "/patient/appointments/{$entry->appointment_id}" : null
                );
            }
        }
    }
}
