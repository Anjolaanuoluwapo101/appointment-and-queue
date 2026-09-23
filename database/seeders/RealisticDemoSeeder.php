<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Hospital;
use App\Models\Notification;
use App\Models\NotificationLog;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\PaymentLog;
use App\Models\Practitioner;
use App\Models\QueueEntry;
use App\Models\QueueNumberSequence;
use App\Models\ScheduleException;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Wipes transactional demo data and reseeds it realistically.
 *
 * KEPT: hospitals, users, staff, practitioners, patients, departments,
 * schedules, settings, consultation fees, appointment slots.
 * WIPED: appointments, payments, payment logs, queue entries + sequences,
 * notifications + logs, audit logs.
 *
 * Performance note: a service-layer version did ~150 pooler roundtrips
 * per appointment (≈50s each). This version computes every row in PHP
 * and bulk-inserts with pre-allocated IDs — a few dozen roundtrips
 * total. Row contents mirror what BookingService / QueueService /
 * PaymentService produce (statuses, references, notice copy, audit
 * actions), including the notification + audit fan-out.
 *
 * Deterministic: mt_srand() fixed, counter-based references.
 */
class RealisticDemoSeeder extends Seeder
{
    private int $refCounter = 0;

    private int $receiptCounter = 0;

    private string $tz = 'Africa/Lagos';

    /** @var array<string, int> deptId|date => last number */
    private array $queueCounters = [];

    /** @var array<string, array<int>> pre-allocated IDs per table */
    private array $idPool = [];

    /** @var array<string, int> */
    private array $idPtr = [];

    public function run(): void
    {
        abort_if(app()->isProduction(), 403, 'Refusing to wipe demo data in production.');

        mt_srand(20260923);

        $hospital = Hospital::where('name', 'Lagoon Specialist Hospital')->first()
            ?? Hospital::where('is_active', true)->orderBy('id')->firstOrFail();
        $this->tz = $hospital->timezone ?? 'Africa/Lagos';

        foreach (['appointments' => 1600, 'payments' => 1100, 'payment_logs' => 1400,
            'queue_entries' => 1300, 'notifications' => 9000, 'notification_logs' => 8000,
            'audit_logs' => 7000, 'appointment_slots' => 1300, 'patients' => 60] as $table => $count) {
            $this->idPool[$table] = $this->nextIds($table, $count);
            $this->idPtr[$table] = 0;
        }

        $this->wipe($hospital);

        $departments = Department::where('hospital_id', $hospital->id)->where('is_active', true)->get();
        $practByDept = [];
        foreach ($departments as $dept) {
            $practByDept[$dept->id] = Practitioner::where('hospital_id', $hospital->id)
                ->where('availability', Practitioner::AVAILABILITY_ACTIVE)
                ->whereHas('departments', fn ($q) => $q->where('departments.id', $dept->id))
                ->pluck('id')->all();
        }
        $patients = Patient::where('hospital_id', $hospital->id)->get();
        $patientUserByPatient = Patient::where('hospital_id', $hospital->id)->pluck('user_id', 'id');
        $receptionists = User::where('hospital_id', $hospital->id)
            ->where('role', User::ROLE_RECEPTIONIST)->where('is_active', true)->pluck('id')->all();
        abort_if(empty($receptionists) || $patients->isEmpty(), 500, 'Need receptionists and patients to seed.');

        $fees = [];
        foreach ($departments as $dept) {
            $fees[$dept->id] = (int) $dept->base_fee_kobo;
        }

        $today = Carbon::now($this->tz)->startOfDay();

        $appointments = [];
        $payments = [];
        $paymentLogs = [];
        $entries = [];
        $notices = [];
        $mailLogs = [];
        $audits = [];
        $newPatients = [];
        $newSlots = [];

        $slotFor = $this->slotFinder($hospital, $newSlots);

        // ---- past 30 days ----
        for ($ago = 30; $ago >= 1; $ago--) {
            $date = $today->copy()->subDays($ago);
            $dow = (int) $date->format('w');
            $target = $dow === 0 ? mt_rand(4, 8) : ($dow === 6 ? mt_rand(8, 14) : mt_rand(26, 42));

            for ($i = 0; $i < $target; $i++) {
                $dept = $this->pickDept($departments);
                $practId = $this->pickPract($practByDept, $dept->id);
                if ($practId === null) {
                    continue;
                }
                $actor = $receptionists[mt_rand(0, count($receptionists) - 1)];

                if (mt_rand(1, 100) <= 8) {
                    $this->makeWalkIn($hospital, $dept, $practId, $actor, $date, $patients,
                        $slotFor, $appointments, $payments, $paymentLogs, $entries,
                        $notices, $mailLogs, $audits, $newPatients, $fees, $patientUserByPatient);
                } else {
                    $this->makeBooking($hospital, $dept, $practId, $patients, $actor, $date,
                        $slotFor, $appointments, $payments, $paymentLogs, $entries,
                        $notices, $mailLogs, $audits, $fees, $patientUserByPatient);
                }
            }
        }

        $this->seedTodayScene($hospital, $departments, $practByDept, $patients, $receptionists, $today,
            $slotFor, $appointments, $payments, $paymentLogs, $entries,
            $notices, $mailLogs, $audits, $newPatients, $fees, $patientUserByPatient);

        // ---- next 7 days: upcoming only ----
        for ($ahead = 1; $ahead <= 7; $ahead++) {
            $date = $today->copy()->addDays($ahead);
            $target = ((int) $date->format('w') === 0) ? 3 : mt_rand(8, 14);
            for ($i = 0; $i < $target; $i++) {
                $dept = $this->pickDept($departments);
                $practId = $this->pickPract($practByDept, $dept->id);
                if ($practId === null) {
                    continue;
                }
                $patient = $patients[mt_rand(0, $patients->count() - 1)];
                $at = $date->copy()->setHour(mt_rand(8, 16))->setMinute(0);
                $mode = $this->modeFor($dept);
                $aid = $this->take('appointments');
                $bookedAt = Carbon::now($this->tz)->subMinutes(mt_rand(10, 3000))->format('Y-m-d H:i:s');
                $appointments[] = [
                    'id' => $aid, 'hospital_id' => $hospital->id, 'patient_id' => $patient->id,
                    'practitioner_id' => $practId, 'department_id' => $dept->id,
                    'slot_id' => $slotFor($practId, $dept->id, $at),
                    'scheduled_at' => $at->format('Y-m-d H:i:s'), 'status' => Appointment::STATUS_SCHEDULED,
                    'payment_mode' => $mode, 'payment_status' => Appointment::PAY_UNPAID, 'payment_id' => null,
                    'is_walk_in' => false, 'cancellation_reason' => null, 'reschedule_count' => 0,
                    'checked_in_by' => null, 'checked_in_at' => null,
                    'created_at' => $bookedAt, 'updated_at' => $bookedAt,
                ];
                $this->notice($notices, $mailLogs, $hospital->id, $patient->id, $patientUserByPatient,
                    'appointment_confirmed', 'Appointment confirmed',
                    "{$dept->name} on {$at->format('D d M H:i')}.", "/patient/appointments/{$aid}", $bookedAt);
                $this->audit($audits, $hospital->id, null, 'appointment_booked', Appointment::class, $aid, null, null, $bookedAt);

                if ($mode === Appointment::PAY_MODE_ONLINE && mt_rand(1, 100) <= 45) {
                    $pid = $this->payOnline($hospital, $dept, $fees, $patient->id, $aid,
                        $payments, $paymentLogs, $notices, $mailLogs, $audits, $patientUserByPatient, $bookedAt, true);
                    $appointments[count($appointments) - 1]['payment_id'] = $pid;
                    $appointments[count($appointments) - 1]['payment_status'] = Appointment::PAY_PAID;
                }
            }
        }

        ScheduleException::firstOrCreate(
            ['hospital_id' => $hospital->id, 'date' => $today->copy()->subDays(mt_rand(3, 20))->toDateString()],
            ['practitioner_id' => array_values($practByDept)[0][0] ?? 1,
                'type' => ScheduleException::TYPE_DAY_OFF, 'reason' => 'Sick day']
        );

        // ---- bulk insert (parents before children for FKs) ----
        $this->bulkInsert('patients', $newPatients);
        $this->bulkInsert('appointment_slots', array_values($newSlots));
        $this->bulkInsert('payments', $payments);
        $this->bulkInsert('appointments', $appointments);
        $this->bulkInsert('payment_logs', $paymentLogs);
        $this->bulkInsert('queue_entries', $entries);
        $this->bulkInsert('notifications', $notices);
        $this->bulkInsert('notification_logs', $mailLogs);
        $this->bulkInsert('audit_logs', $audits);

        $seqRows = [];
        foreach ($this->queueCounters as $key => $last) {
            [$deptId, $day] = explode('|', $key);
            $seqRows[] = ['hospital_id' => $hospital->id, 'department_id' => $deptId,
                'queue_date' => $day, 'last_number' => $last,
                'created_at' => $day.' 08:00:00', 'updated_at' => $day.' 08:00:00'];
        }
        $this->bulkInsert('queue_number_sequences', $seqRows);

        DB::statement("UPDATE appointment_slots s SET booked_count = COALESCE((SELECT COUNT(*) FROM appointments a WHERE a.slot_id = s.id AND a.status <> 'cancelled'), 0), updated_at = NOW() WHERE s.hospital_id = ? AND s.id IN (SELECT DISTINCT slot_id FROM appointments WHERE hospital_id = ? AND slot_id IS NOT NULL)", [$hospital->id, $hospital->id]);

        $this->command->info('Appointments: '.Appointment::where('hospital_id', $hospital->id)->count()
            .' | Queue: '.QueueEntry::where('hospital_id', $hospital->id)->count()
            .' | Payments: '.Payment::where('hospital_id', $hospital->id)->count()
            .' | Notifications: '.Notification::where('hospital_id', $hospital->id)->count()
            .' | Audit: '.AuditLog::where('hospital_id', $hospital->id)->count());
    }

    // ------------------------------------------------------------------
    // Visit builders
    // ------------------------------------------------------------------

    private function makeBooking(
        Hospital $hospital, Department $dept, int $practId, $patients, int $actor, Carbon $date,
        callable $slotFor,
        array &$appointments, array &$payments, array &$paymentLogs, array &$entries,
        array &$notices, array &$mailLogs, array &$audits,
        array $fees, $patientUserByPatient,
    ): void {
        $patient = $patients[mt_rand(0, $patients->count() - 1)];
        $at = $date->copy()->setHour(mt_rand(8, 16))->setMinute(0);
        $mode = $this->modeFor($dept);
        $aid = $this->take('appointments');
        $bookedAt = $at->copy()->subHours(mt_rand(0, 240))->format('Y-m-d H:i:s');
        $when = $at->format('D d M H:i');

        $row = [
            'id' => $aid, 'hospital_id' => $hospital->id, 'patient_id' => $patient->id,
            'practitioner_id' => $practId, 'department_id' => $dept->id,
            'slot_id' => $slotFor($practId, $dept->id, $at),
            'scheduled_at' => $at->format('Y-m-d H:i:s'), 'status' => Appointment::STATUS_SCHEDULED,
            'payment_mode' => $mode, 'payment_status' => Appointment::PAY_UNPAID, 'payment_id' => null,
            'is_walk_in' => false, 'cancellation_reason' => null, 'reschedule_count' => 0,
            'checked_in_by' => null, 'checked_in_at' => null,
            'created_at' => $bookedAt, 'updated_at' => $bookedAt,
        ];

        $this->notice($notices, $mailLogs, $hospital->id, $patient->id, $patientUserByPatient,
            'appointment_confirmed', 'Appointment confirmed', "{$dept->name} on {$when}.",
            "/patient/appointments/{$aid}", $bookedAt);
        $this->staffNotice($notices, $mailLogs, $hospital->id, 'appointment_booked', 'New appointment booked',
            "{$patient->full_name} — {$dept->name} on {$when}.", '/staff/appointments', $bookedAt);
        $this->audit($audits, $hospital->id, null, 'appointment_booked', Appointment::class, $aid, null, null, $bookedAt);

        $roll = mt_rand(1, 100);

        if ($roll <= 6) {
            // Cancelled with reasons; online-paid ones refund.
            $reasons = ['Patient request', 'Patient request', 'Doctor unavailable', 'Duplicate booking', 'No transport fare', 'Emergency elsewhere'];
            $reason = $reasons[mt_rand(0, count($reasons) - 1)];
            $doneAt = $at->copy()->subHours(mt_rand(0, 48))->format('Y-m-d H:i:s');

            if ($mode === Appointment::PAY_MODE_ONLINE && mt_rand(1, 100) <= 55) {
                $pid = $this->payOnline($hospital, $dept, $fees, $patient->id, $aid,
                    $payments, $paymentLogs, $notices, $mailLogs, $audits, $patientUserByPatient, $bookedAt, (bool) mt_rand(0, 1));
                $row['payment_id'] = $pid;
                $row['payment_status'] = Appointment::PAY_PAID;
                $this->refundPaid($hospital, $patient->id, $aid, $payments, $paymentLogs,
                    $notices, $mailLogs, $audits, $patientUserByPatient, $doneAt);
                $row['payment_status'] = Appointment::PAY_REFUNDED;
            }

            $row['status'] = Appointment::STATUS_CANCELLED;
            $row['cancellation_reason'] = $reason;
            $row['updated_at'] = $doneAt;

            $byPatient = mt_rand(1, 100) <= 60;
            if ($byPatient) {
                $this->staffNotice($notices, $mailLogs, $hospital->id, 'appointment_cancelled',
                    'Appointment cancelled by patient',
                    "{$patient->full_name} cancelled {$when}.", '/staff/appointments', $doneAt);
                $byUser = $patientUserByPatient[$patient->id] ?? null;
            } else {
                $this->notice($notices, $mailLogs, $hospital->id, $patient->id, $patientUserByPatient,
                    'appointment_cancelled', 'Appointment cancelled', 'Your appointment was cancelled.',
                    "/patient/appointments/{$aid}", $doneAt);
                $byUser = $actor;
            }
            $this->audit($audits, $hospital->id, $byUser, 'appointment_cancelled', Appointment::class, $aid,
                ['status' => Appointment::STATUS_SCHEDULED], ['status' => Appointment::STATUS_CANCELLED], $doneAt);
            $appointments[] = $row;

            return;
        }

        if ($roll <= 12) {
            $doneAt = $at->format('Y-m-d H:i:s');
            $row['status'] = Appointment::STATUS_NO_SHOW;
            $row['updated_at'] = $doneAt;
            $appointments[] = $row;
            $this->audit($audits, $hospital->id, $actor, 'appointment_no_show', Appointment::class, $aid,
                ['status' => Appointment::STATUS_SCHEDULED], ['status' => Appointment::STATUS_NO_SHOW], $doneAt);

            return;
        }

        // Completed lifecycle (occasionally rescheduled first).
        if ($mode === Appointment::PAY_MODE_ONLINE) {
            $r = mt_rand(1, 100);
            if ($r <= 90) {
                $pid = $this->payOnline($hospital, $dept, $fees, $patient->id, $aid,
                    $payments, $paymentLogs, $notices, $mailLogs, $audits, $patientUserByPatient, $bookedAt, (bool) mt_rand(0, 1));
                $row['payment_id'] = $pid;
                $row['payment_status'] = Appointment::PAY_PAID;
            } elseif ($r <= 96) {
                $this->payFailed($hospital, $dept, $fees, $patient->id, $aid,
                    $payments, $paymentLogs, $notices, $mailLogs, $audits, $patientUserByPatient, $bookedAt);
                $row['payment_status'] = Appointment::PAY_FAILED;
            }
        }

        if (mt_rand(1, 100) <= 8) {
            $later = $at->copy()->addDays(mt_rand(1, 5))->setHour(mt_rand(8, 16));
            $row['slot_id'] = $slotFor($practId, $dept->id, $later);
            $row['scheduled_at'] = $later->format('Y-m-d H:i:s');
            $row['reschedule_count'] = 1;
            $at = $later;
            if (mt_rand(1, 100) <= 20) {
                $evenLater = $at->copy()->addDays(mt_rand(1, 3));
                $row['slot_id'] = $slotFor($practId, $dept->id, $evenLater);
                $row['scheduled_at'] = $evenLater->format('Y-m-d H:i:s');
                $row['reschedule_count'] = 2;
                $at = $evenLater;
            }
            $this->notice($notices, $mailLogs, $hospital->id, $patient->id, $patientUserByPatient,
                'appointment_rescheduled', 'Appointment rescheduled',
                "Moved to {$at->format('D d M H:i')}. Your payment carries over.",
                "/patient/appointments/{$aid}", $at->format('Y-m-d H:i:s'));
            $this->audit($audits, $hospital->id, null, 'appointment_rescheduled', Appointment::class, $aid,
                null, ['reschedule_count' => $row['reschedule_count']], $at->format('Y-m-d H:i:s'));
        }

        $this->runQueue($hospital, $dept, $practId, $patient->id, $patient->full_name, $actor, $at, $row,
            $entries, $notices, $mailLogs, $audits, $patientUserByPatient, $payments, $paymentLogs, $fees);
        $appointments[] = $row;
    }

    /**
     * Full check-in → queue lifecycle with randomised gaps and
     * skip/recall/cancel branches. Mutates $row in place.
     *
     * @param  array<string,mixed>  $row
     */
    private function runQueue(
        Hospital $hospital, Department $dept, int $practId, int $patientId, string $patientName,
        int $actor, Carbon $at, array &$row,
        array &$entries, array &$notices, array &$mailLogs, array &$audits,
        $patientUserByPatient, array &$payments, array &$paymentLogs, array $fees,
    ): void {
        $checked = $at->copy()->subMinutes(mt_rand(5, 40))->format('Y-m-d H:i:s');
        $row['checked_in_by'] = $actor;
        $row['checked_in_at'] = $checked;

        if ($row['payment_mode'] === Appointment::PAY_MODE_PHYSICAL && $row['payment_status'] === Appointment::PAY_UNPAID) {
            $pid = $this->addManualPayment($hospital, $dept, $fees, $patientId, $row['id'],
                $payments, $paymentLogs, $checked, ['cash', 'cash', 'transfer', 'pos'][mt_rand(0, 3)]);
            $row['payment_id'] = $pid;
            $row['payment_status'] = Appointment::PAY_PAID;
            $this->notice($notices, $mailLogs, $hospital->id, $patientId, $patientUserByPatient,
                'payment_cleared', 'Payment cleared — now in queue',
                'Your payment was confirmed at the desk. Track your position live.',
                "/patient/appointments/{$row['id']}", $checked);
            $this->audit($audits, $hospital->id, $actor, 'payment_cleared_manual', Appointment::class, $row['id'],
                ['payment_status' => Appointment::PAY_UNPAID], ['payment_status' => Appointment::PAY_PAID], $checked);
            $row['status'] = Appointment::STATUS_IN_QUEUE;
        } elseif ($row['payment_status'] === Appointment::PAY_PAID) {
            $row['status'] = Appointment::STATUS_IN_QUEUE;
        } else {
            $row['status'] = Appointment::STATUS_PENDING_CLEARANCE;
            $row['updated_at'] = $checked;
            $this->notice($notices, $mailLogs, $hospital->id, $patientId, $patientUserByPatient,
                'clearance_pending', 'Payment clearance pending',
                'You are checked in. Please pay at the desk to join the queue.',
                "/patient/appointments/{$row['id']}", $checked);
            $this->audit($audits, $hospital->id, $actor, 'checked_in', Appointment::class, $row['id'],
                ['status' => Appointment::STATUS_SCHEDULED], ['status' => Appointment::STATUS_PENDING_CLEARANCE], $checked);

            return;
        }

        $this->notice($notices, $mailLogs, $hospital->id, $patientId, $patientUserByPatient,
            'checked_in', 'Checked in — queue number assigned', 'Track your position live.',
            "/patient/appointments/{$row['id']}", $checked);
        $this->audit($audits, $hospital->id, $actor, 'checked_in', Appointment::class, $row['id'],
            ['status' => Appointment::STATUS_SCHEDULED], ['status' => Appointment::STATUS_IN_QUEUE], $checked);

        $number = $this->nextQueueNumber($dept, $at->toDateString());
        $eid = $this->take('queue_entries');
        $entry = [
            'id' => $eid, 'hospital_id' => $hospital->id, 'department_id' => $dept->id,
            'practitioner_id' => $practId, 'patient_id' => $patientId, 'appointment_id' => $row['id'],
            'queue_number' => $number, 'queue_date' => $at->toDateString(),
            'status' => QueueEntry::STATUS_WAITING, 'is_recalled' => false, 'cancel_reason' => null,
            'called_at' => null, 'started_consultation_at' => null, 'completed_at' => null,
            'action_by' => $actor, 'created_at' => $checked, 'updated_at' => $checked,
        ];

        $r = mt_rand(1, 100);
        $called = $at->copy()->addMinutes(mt_rand(5, 50))->format('Y-m-d H:i:s');

        $callNotice = function () use (&$notices, &$mailLogs, $hospital, $patientId, $patientUserByPatient,
            $number, $dept, $row, $called
        ): void {
            $this->notice($notices, $mailLogs, $hospital->id, $patientId, $patientUserByPatient,
                'called', "Called — {$number}",
                'Please proceed'.($dept->room_label ? " to {$dept->room_label}." : '.'),
                "/patient/appointments/{$row['id']}", $called);
        };

        if ($r <= 5) {
            $entry['status'] = QueueEntry::STATUS_SKIPPED;
            $entry['called_at'] = $called;
            $entry['updated_at'] = $called;
            $this->audit($audits, $hospital->id, $actor, 'queue_called', QueueEntry::class, $eid,
                ['status' => 'waiting'], ['status' => 'called'], $called);
            $this->audit($audits, $hospital->id, $actor, 'queue_skipped', QueueEntry::class, $eid,
                ['status' => 'called'], ['status' => 'skipped'], $called);
            $callNotice();
        } elseif ($r <= 8) {
            $entry['status'] = QueueEntry::STATUS_CANCELLED;
            $entry['called_at'] = $called;
            $entry['cancel_reason'] = ['Patient left', 'Duplicate entry', 'Wrong department'][mt_rand(0, 2)];
            $entry['updated_at'] = $called;
            $this->audit($audits, $hospital->id, $actor, 'queue_entry_cancelled', QueueEntry::class, $eid,
                ['status' => 'called'], ['status' => 'cancelled'], $called);
            $this->notice($notices, $mailLogs, $hospital->id, $patientId, $patientUserByPatient,
                'queue_entry_cancelled', 'Queue entry cancelled',
                "Your queue place ({$number}) was cancelled by staff. Your appointment record is kept.",
                "/patient/appointments/{$row['id']}", $called);
        } else {
            if ($r <= 15) {
                $this->audit($audits, $hospital->id, $actor, 'queue_skipped', QueueEntry::class, $eid,
                    ['status' => 'called'], ['status' => 'skipped'], $called);
                $this->audit($audits, $hospital->id, $actor, 'queue_recalled', QueueEntry::class, $eid,
                    ['status' => 'skipped'], ['status' => 'waiting'], $called);
                $entry['is_recalled'] = true;
            }
            $started = Carbon::parse($called)->addMinutes(mt_rand(2, 15))->format('Y-m-d H:i:s');
            $completed = Carbon::parse($started)->addMinutes(mt_rand(10, 45))->format('Y-m-d H:i:s');
            $entry['status'] = QueueEntry::STATUS_COMPLETED;
            $entry['called_at'] = $called;
            $entry['started_consultation_at'] = $started;
            $entry['completed_at'] = $completed;
            $entry['updated_at'] = $completed;
            $row['status'] = Appointment::STATUS_COMPLETED;
            $row['updated_at'] = $completed;
            $this->audit($audits, $hospital->id, $actor, 'queue_called', QueueEntry::class, $eid,
                ['status' => 'waiting'], ['status' => 'called'], $called);
            $this->audit($audits, $hospital->id, $actor, 'consultation_started', QueueEntry::class, $eid,
                ['status' => 'called'], ['status' => 'in_consultation'], $started);
            $this->audit($audits, $hospital->id, $actor, 'consultation_completed', QueueEntry::class, $eid,
                ['status' => 'in_consultation'], ['status' => 'completed'], $completed);
            $callNotice();
            $this->notice($notices, $mailLogs, $hospital->id, $patientId, $patientUserByPatient,
                'consultation_completed', 'Consultation completed',
                "Your visit ({$number}) is marked complete.",
                "/patient/appointments/{$row['id']}", $completed);
        }

        $entries[] = $entry;
    }

    private function makeWalkIn(
        Hospital $hospital, Department $dept, int $practId, int $actor, Carbon $date,
        $patients, callable $slotFor,
        array &$appointments, array &$payments, array &$paymentLogs, array &$entries,
        array &$notices, array &$mailLogs, array &$audits, array &$newPatients,
        array $fees, $patientUserByPatient,
    ): void {
        if (mt_rand(1, 3) === 1) {
            $phone = '0803'.str_pad((string) mt_rand(1000000, 9999999), 7, '0', STR_PAD_LEFT);
            $names = ['Adaeze Udeh', 'Olumide Fashola', 'Hauwa Sani', 'Ibrahim Danladi', 'Ngozi Anyanwu', 'Segun Ajiboye'];
            $pid = $this->take('patients');
            $newPatients[] = [
                'id' => $pid, 'hospital_id' => $hospital->id, 'user_id' => null,
                'full_name' => $names[mt_rand(0, count($names) - 1)], 'phone' => $phone,
                'email' => null, 'date_of_birth' => null, 'gender' => null, 'address' => null,
                'secondary_contact_name' => null, 'secondary_contact_phone' => null,
                'created_at' => $date->format('Y-m-d H:i:s'), 'updated_at' => $date->format('Y-m-d H:i:s'),
            ];
            $patientId = $pid;
            $patientName = end($newPatients)['full_name'];
        } else {
            $existing = $patients[mt_rand(0, $patients->count() - 1)];
            $patientId = $existing->id;
            $patientName = $existing->full_name;
        }

        $at = $date->copy()->setHour(mt_rand(8, 16))->setMinute([0, 15, 30, 45][mt_rand(0, 3)]);
        $aid = $this->take('appointments');
        $checked = $at->copy()->subMinutes(mt_rand(5, 40))->format('Y-m-d H:i:s');
        $method = ['cash', 'cash', 'transfer', 'pos'][mt_rand(0, 3)];

        $pid = $this->addManualPayment($hospital, $dept, $fees, $patientId, $aid,
            $payments, $paymentLogs, $checked, $method);

        $number = $this->nextQueueNumber($dept, $at->toDateString());
        $eid = $this->take('queue_entries');
        $called = Carbon::parse($checked)->addMinutes(mt_rand(5, 50))->format('Y-m-d H:i:s');
        $started = Carbon::parse($called)->addMinutes(mt_rand(2, 15))->format('Y-m-d H:i:s');
        $completed = Carbon::parse($started)->addMinutes(mt_rand(10, 45))->format('Y-m-d H:i:s');

        $entries[] = [
            'id' => $eid, 'hospital_id' => $hospital->id, 'department_id' => $dept->id,
            'practitioner_id' => $practId, 'patient_id' => $patientId, 'appointment_id' => $aid,
            'queue_number' => $number, 'queue_date' => $at->toDateString(),
            'status' => QueueEntry::STATUS_COMPLETED, 'is_recalled' => false, 'cancel_reason' => null,
            'called_at' => $called, 'started_consultation_at' => $started, 'completed_at' => $completed,
            'action_by' => $actor, 'created_at' => $checked, 'updated_at' => $completed,
        ];

        $appointments[] = [
            'id' => $aid, 'hospital_id' => $hospital->id, 'patient_id' => $patientId,
            'practitioner_id' => $practId, 'department_id' => $dept->id, 'slot_id' => null,
            'scheduled_at' => $at->format('Y-m-d H:i:s'), 'status' => Appointment::STATUS_COMPLETED,
            'payment_mode' => Appointment::PAY_MODE_PHYSICAL, 'payment_status' => Appointment::PAY_PAID,
            'payment_id' => $pid, 'is_walk_in' => true, 'cancellation_reason' => null, 'reschedule_count' => 0,
            'checked_in_by' => $actor, 'checked_in_at' => $checked,
            'created_at' => $checked, 'updated_at' => $completed,
        ];

        $this->notice($notices, $mailLogs, $hospital->id, $patientId, $patientUserByPatient,
            'walk_in_registered', 'Walk-in registered', "You were queued as {$number}.", null, $checked);
        $this->audit($audits, $hospital->id, $actor, 'walk_in_registered', QueueEntry::class, $eid, null, null, $checked);
        $this->audit($audits, $hospital->id, $actor, 'checked_in', Appointment::class, $aid,
            ['status' => Appointment::STATUS_SCHEDULED], ['status' => Appointment::STATUS_COMPLETED], $checked);
    }

    private function seedTodayScene(
        Hospital $hospital, $departments, $practByDept, $patients, $receptionists, Carbon $today,
        callable $slotFor,
        array &$appointments, array &$payments, array &$paymentLogs, array &$entries,
        array &$notices, array &$mailLogs, array &$audits, array &$newPatients,
        array $fees, $patientUserByPatient,
    ): void {
        $ordered = $departments->sortBy('id')->values();
        $liveDepts = $ordered->slice(mt_rand(0, max(0, $ordered->count() - 4)), 4)->values();

        foreach ($liveDepts as $di => $dept) {
            $practId = $this->pickPract($practByDept, $dept->id);
            if ($practId === null) {
                continue;
            }

            $states = $di === 0
                ? ['completed', 'completed', 'in_consultation', 'called', 'waiting', 'waiting', 'pending', 'scheduled', 'scheduled']
                : ['completed', 'waiting', 'waiting', 'scheduled'];

            foreach ($states as $state) {
                $patient = $patients[mt_rand(0, $patients->count() - 1)];
                $at = $today->copy()->setHour(mt_rand(8, 14))->setMinute([0, 15, 30, 45][mt_rand(0, 3)]);
                $actor = $receptionists[mt_rand(0, count($receptionists) - 1)];
                $mode = $this->modeFor($dept);
                $aid = $this->take('appointments');
                $bookedAt = $at->copy()->subHours(mt_rand(1, 72))->format('Y-m-d H:i:s');
                $now = Carbon::now($this->tz)->format('Y-m-d H:i:s');

                $row = [
                    'id' => $aid, 'hospital_id' => $hospital->id, 'patient_id' => $patient->id,
                    'practitioner_id' => $practId, 'department_id' => $dept->id,
                    'slot_id' => $slotFor($practId, $dept->id, $at),
                    'scheduled_at' => $at->format('Y-m-d H:i:s'),
                    'status' => Appointment::STATUS_SCHEDULED,
                    'payment_mode' => $mode, 'payment_status' => Appointment::PAY_UNPAID, 'payment_id' => null,
                    'is_walk_in' => false, 'cancellation_reason' => null, 'reschedule_count' => 0,
                    'checked_in_by' => null, 'checked_in_at' => null,
                    'created_at' => $bookedAt, 'updated_at' => $bookedAt,
                ];
                $this->notice($notices, $mailLogs, $hospital->id, $patient->id, $patientUserByPatient,
                    'appointment_confirmed', 'Appointment confirmed',
                    "{$dept->name} on {$at->format('D d M H:i')}.", "/patient/appointments/{$aid}", $bookedAt);
                $this->audit($audits, $hospital->id, null, 'appointment_booked', Appointment::class, $aid, null, null, $bookedAt);

                if ($state === 'scheduled') {
                    if ($mode === Appointment::PAY_MODE_ONLINE && mt_rand(1, 100) <= 50) {
                        $row['payment_id'] = $this->payOnline($hospital, $dept, $fees, $patient->id, $aid,
                            $payments, $paymentLogs, $notices, $mailLogs, $audits, $patientUserByPatient, $bookedAt, true);
                        $row['payment_status'] = Appointment::PAY_PAID;
                    }
                    $appointments[] = $row;

                    continue;
                }

                if ($mode === Appointment::PAY_MODE_ONLINE) {
                    $row['payment_id'] = $this->payOnline($hospital, $dept, $fees, $patient->id, $aid,
                        $payments, $paymentLogs, $notices, $mailLogs, $audits, $patientUserByPatient, $bookedAt, true);
                    $row['payment_status'] = Appointment::PAY_PAID;
                }

                $checked = $at->copy()->subMinutes(mt_rand(5, 30))->format('Y-m-d H:i:s');
                $row['checked_in_by'] = $actor;
                $row['checked_in_at'] = $checked;

                if ($row['payment_status'] === Appointment::PAY_UNPAID) {
                    $row['status'] = Appointment::STATUS_PENDING_CLEARANCE;
                    $row['updated_at'] = $checked;
                    $appointments[] = $row;
                    $this->notice($notices, $mailLogs, $hospital->id, $patient->id, $patientUserByPatient,
                        'clearance_pending', 'Payment clearance pending',
                        'You are checked in. Please pay at the desk to join the queue.',
                        "/patient/appointments/{$aid}", $checked);

                    continue;
                }

                $row['status'] = Appointment::STATUS_IN_QUEUE;
                $number = $this->nextQueueNumber($dept, $at->toDateString());
                $eid = $this->take('queue_entries');
                $entry = [
                    'id' => $eid, 'hospital_id' => $hospital->id, 'department_id' => $dept->id,
                    'practitioner_id' => $practId, 'patient_id' => $patient->id, 'appointment_id' => $aid,
                    'queue_number' => $number, 'queue_date' => $at->toDateString(),
                    'status' => QueueEntry::STATUS_WAITING, 'is_recalled' => false, 'cancel_reason' => null,
                    'called_at' => null, 'started_consultation_at' => null, 'completed_at' => null,
                    'action_by' => $actor, 'created_at' => $checked, 'updated_at' => $checked,
                ];

                if (in_array($state, ['called', 'in_consultation', 'completed'], true)) {
                    $entry['status'] = QueueEntry::STATUS_CALLED;
                    $entry['called_at'] = $now;
                    $entry['updated_at'] = $now;
                    $this->notice($notices, $mailLogs, $hospital->id, $patient->id, $patientUserByPatient,
                        'called', "Called — {$number}",
                        'Please proceed'.($dept->room_label ? " to {$dept->room_label}." : '.'),
                        "/patient/appointments/{$aid}", $now);
                }
                if (in_array($state, ['in_consultation', 'completed'], true)) {
                    $entry['status'] = QueueEntry::STATUS_IN_CONSULTATION;
                    $entry['started_consultation_at'] = $now;
                    $entry['updated_at'] = $now;
                }
                if ($state === 'completed') {
                    $entry['status'] = QueueEntry::STATUS_COMPLETED;
                    $entry['completed_at'] = $now;
                    $entry['updated_at'] = $now;
                    $row['status'] = Appointment::STATUS_COMPLETED;
                    $row['updated_at'] = $now;
                    $this->notice($notices, $mailLogs, $hospital->id, $patient->id, $patientUserByPatient,
                        'consultation_completed', 'Consultation completed',
                        "Your visit ({$number}) is marked complete.",
                        "/patient/appointments/{$aid}", $now);
                }

                $entries[] = $entry;
                $appointments[] = $row;
                $this->notice($notices, $mailLogs, $hospital->id, $patient->id, $patientUserByPatient,
                    'checked_in', 'Checked in — queue number assigned',
                    "Your queue number is {$number}. Track your position live.",
                    "/patient/appointments/{$aid}", $checked);
            }
        }
    }

    // ------------------------------------------------------------------
    // Payments (rows only; notices + audit included)
    // ------------------------------------------------------------------

    /**
     * @return int payment id
     */
    private function payOnline(
        Hospital $hospital, Department $dept, array $fees, int $patientId, int $aid,
        array &$payments, array &$paymentLogs,
        array &$notices, array &$mailLogs, array &$audits, $patientUserByPatient,
        string $at, bool $webhook,
    ): int {
        $pid = $this->take('payments');
        $ref = 'PSK-'.str_pad((string) $aid, 6, '0', STR_PAD_LEFT).'-'.str_pad((string) $this->refCounter++, 4, '0', STR_PAD_LEFT);
        $payments[] = [
            'id' => $pid, 'hospital_id' => $hospital->id, 'appointment_id' => $aid,
            'patient_id' => $patientId, 'provider' => Payment::PROVIDER_PAYSTACK,
            'reference' => $ref, 'amount_kobo' => $fees[$dept->id] ?? 0,
            'status' => Payment::STATUS_SUCCESS,
            'paid_at' => $at, 'verified_via_webhook' => $webhook,
            'receipt_no' => null, 'method' => null, 'metadata' => null,
            'created_at' => $at, 'updated_at' => $at,
        ];
        $paymentLogs[] = [
            'hospital_id' => $hospital->id, 'payment_id' => $pid, 'event' => $webhook ? 'webhook' : 'verify',
            'payload' => json_encode(['reference' => $ref]), 'result' => 'paid',
            'created_at' => $at, 'updated_at' => $at,
        ];
        $this->notice($notices, $mailLogs, $hospital->id, $patientId, $patientUserByPatient,
            'payment_success', 'Payment successful',
            "Receipt ref {$ref}. Show this at check-in or proceed straight to the queue.",
            "/patient/appointments/{$aid}", $at);
        $this->audit($audits, $hospital->id, null, 'payment_paid', Payment::class, $pid,
            ['status' => Payment::STATUS_PENDING], ['status' => Payment::STATUS_SUCCESS], $at);

        return $pid;
    }

    private function payFailed(
        Hospital $hospital, Department $dept, array $fees, int $patientId, int $aid,
        array &$payments, array &$paymentLogs,
        array &$notices, array &$mailLogs, array &$audits, $patientUserByPatient,
        string $at,
    ): void {
        $pid = $this->take('payments');
        $ref = 'PSK-'.str_pad((string) $aid, 6, '0', STR_PAD_LEFT).'-F'.($this->refCounter++);
        $payments[] = [
            'id' => $pid, 'hospital_id' => $hospital->id, 'appointment_id' => $aid,
            'patient_id' => $patientId, 'provider' => Payment::PROVIDER_PAYSTACK,
            'reference' => $ref, 'amount_kobo' => $fees[$dept->id] ?? 0,
            'status' => Payment::STATUS_FAILED, 'paid_at' => null, 'verified_via_webhook' => false,
            'receipt_no' => null, 'method' => null, 'metadata' => null,
            'created_at' => $at, 'updated_at' => $at,
        ];
        $paymentLogs[] = [
            'hospital_id' => $hospital->id, 'payment_id' => $pid, 'event' => 'initialize',
            'payload' => json_encode(['reference' => $ref]), 'result' => 'failed',
            'created_at' => $at, 'updated_at' => $at,
        ];
        $this->notice($notices, $mailLogs, $hospital->id, $patientId, $patientUserByPatient,
            'payment_failed', 'Payment failed', 'Your online payment could not be completed. Please retry.',
            "/patient/appointments/{$aid}", $at);
    }

    private function refundPaid(
        Hospital $hospital, int $patientId, int $aid,
        array &$payments, array &$paymentLogs,
        array &$notices, array &$mailLogs, array &$audits, $patientUserByPatient,
        string $at,
    ): void {
        foreach ($payments as &$p) {
            if ($p['appointment_id'] === $aid && $p['status'] === Payment::STATUS_SUCCESS) {
                $p['status'] = Payment::STATUS_REFUNDED;
                $p['updated_at'] = $at;
                $paymentLogs[] = [
                    'hospital_id' => $hospital->id, 'payment_id' => $p['id'], 'event' => 'refund',
                    'payload' => json_encode(['reference' => $p['reference']]), 'result' => 'ok',
                    'created_at' => $at, 'updated_at' => $at,
                ];
                $this->notice($notices, $mailLogs, $hospital->id, $patientId, $patientUserByPatient,
                    'payment_refunded', 'Payment refunded',
                    "Ref {$p['reference']} was refunded automatically after cancellation.",
                    "/patient/appointments/{$aid}", $at);
                $this->audit($audits, $hospital->id, null, 'payment_refunded', Payment::class, $p['id'],
                    ['status' => Payment::STATUS_SUCCESS], ['status' => Payment::STATUS_REFUNDED], $at);

                return;
            }
        }
        unset($p);
    }

    /**
     * Physical desk collection.
     *
     * @return int payment id
     */
    private function addManualPayment(
        Hospital $hospital, Department $dept, array $fees, int $patientId, int $aid,
        array &$payments, array &$paymentLogs, string $at, string $method = 'cash',
    ): int {
        $pid = $this->take('payments');
        $ref = 'RCPT-'.str_pad((string) ++$this->receiptCounter, 5, '0', STR_PAD_LEFT);
        $payments[] = [
            'id' => $pid, 'hospital_id' => $hospital->id, 'appointment_id' => $aid,
            'patient_id' => $patientId, 'provider' => Payment::PROVIDER_MANUAL,
            'reference' => $ref, 'amount_kobo' => $fees[$dept->id] ?? 0,
            'status' => Payment::STATUS_SUCCESS, 'paid_at' => $at, 'verified_via_webhook' => false,
            'receipt_no' => $ref, 'method' => $method, 'metadata' => null,
            'created_at' => $at, 'updated_at' => $at,
        ];

        return $pid;
    }

    // ------------------------------------------------------------------
    // Generic helpers
    // ------------------------------------------------------------------

    private function nextQueueNumber(Department $dept, string $day): string
    {
        $key = $dept->id.'|'.$day;
        $this->queueCounters[$key] = ($this->queueCounters[$key] ?? 0) + 1;

        return $dept->queue_prefix.str_pad((string) $this->queueCounters[$key], 3, '0', STR_PAD_LEFT);
    }

    private function nextIds(string $table, int $count): array
    {
        $seq = $table.'_id_seq';
        $rows = DB::select("SELECT nextval('{$seq}') AS id FROM generate_series(1, {$count})");

        return array_map(fn ($r) => (int) $r->id, $rows);
    }

    /** @param array<int, array<string,mixed>> $rows */
    private function bulkInsert(string $table, array $rows, int $chunk = 250): void
    {
        foreach (array_chunk($rows, $chunk) as $piece) {
            DB::table($table)->insert($piece);
        }
    }

    private function take(string $table, int $n = 1)
    {
        $out = array_slice($this->idPool[$table], $this->idPtr[$table], $n);
        $this->idPtr[$table] += $n;

        if (count($out) < $n) {
            throw new \RuntimeException("ID pool exhausted for {$table}.");
        }

        return $n === 1 ? $out[0] : $out;
    }

    private function pickDept($departments): Department
    {
        $weights = [
            'General OPD' => 22, 'Emergency Care' => 14, 'Cardiology' => 10,
            'Paediatrics' => 12, 'Obstetrics & Gynaecology' => 10, 'Orthopaedics' => 8,
            'ENT (Ear Nose Throat)' => 7, 'Ophthalmology' => 6, 'Dermatology' => 6,
            'Dental Clinic' => 5,
        ];

        $total = 0;
        foreach ($departments as $dept) {
            $total += $weights[$dept->name] ?? 5;
        }

        $pick = mt_rand(1, $total);
        foreach ($departments as $dept) {
            $pick -= $weights[$dept->name] ?? 5;
            if ($pick <= 0) {
                return $dept;
            }
        }

        return $departments->first();
    }

    private function pickPract(array $practByDept, int $deptId): ?int
    {
        $list = $practByDept[$deptId] ?? [];

        return $list === [] ? null : $list[mt_rand(0, count($list) - 1)];
    }

    private function modeFor(Department $dept): string
    {
        return match ($dept->payment_mode) {
            Department::PAYMENT_ONLINE_REQUIRED => Appointment::PAY_MODE_ONLINE,
            Department::PAYMENT_PHYSICAL_ONLY => Appointment::PAY_MODE_PHYSICAL,
            default => mt_rand(1, 100) <= 55 ? Appointment::PAY_MODE_ONLINE : Appointment::PAY_MODE_PHYSICAL,
        };
    }

    /**
     * Finds or stages a slot with remaining capacity for the exact
     * practitioner/department/start. Returns the slot id.
     */
    private function slotFinder(Hospital $hospital, array &$newSlots): callable
    {
        $existing = AppointmentSlot::where('hospital_id', $hospital->id)
            ->get(['id', 'practitioner_id', 'department_id', 'starts_at', 'capacity', 'booked_count'])
            ->keyBy(fn ($s) => $s->practitioner_id.'|'.$s->department_id.'|'.$s->starts_at);
        $used = [];
        foreach ($existing as $key => $s) {
            $used[$key] = (int) $s->booked_count;
        }

        return function (int $practId, int $deptId, Carbon $at) use ($hospital, &$existing, &$newSlots, &$used): int {
            $at = $at->copy()->setMinute([0, 15, 30, 45][mt_rand(0, 3)])->setSecond(0);

            for ($t = 0; $t < 12; $t++) {
                $key = $practId.'|'.$deptId.'|'.$at->format('Y-m-d H:i:s');

                if (! isset($existing[$key]) && ! isset($newSlots[$key])) {
                    $newSlots[$key] = [
                        'id' => $this->take('appointment_slots'), 'hospital_id' => $hospital->id,
                        'practitioner_id' => $practId, 'department_id' => $deptId,
                        'date' => $at->toDateString(),
                        'starts_at' => $at->format('Y-m-d H:i:s'),
                        'ends_at' => $at->copy()->addMinutes(30)->format('Y-m-d H:i:s'),
                        'capacity' => mt_rand(2, 5), 'booked_count' => 0, 'is_active' => true,
                        'created_at' => $at->format('Y-m-d H:i:s'), 'updated_at' => $at->format('Y-m-d H:i:s'),
                    ];
                    $used[$key] = 0;
                }

                $cap = $newSlots[$key]['capacity'] ?? $existing[$key]->capacity;
                if ($used[$key] < $cap) {
                    $used[$key]++;

                    return $newSlots[$key]['id'] ?? $existing[$key]->id;
                }

                $at->addMinutes(15);
            }

            throw new \RuntimeException('No free slot while seeding.');
        };
    }

    private function notice(
        array &$notices, array &$mailLogs, int $hospitalId, int $patientId, $patientUserByPatient,
        string $type, string $title, string $body, ?string $link, string $at,
    ): void {
        $uid = $patientUserByPatient[$patientId] ?? null;

        if ($uid === null) {
            return;
        }

        $nid = $this->take('notifications');
        $notices[] = [
            'id' => $nid, 'hospital_id' => $hospitalId, 'user_id' => $uid,
            'type' => $type, 'title' => $title, 'body' => $body, 'link' => $link,
            'read_at' => mt_rand(1, 100) <= 35 ? $at : null,
            'created_at' => $at, 'updated_at' => $at,
        ];
        $mailLogs[] = [
            'hospital_id' => $hospitalId, 'notification_id' => $nid, 'channel' => 'mail',
            'recipient' => 'user'.$uid.'@example.com', 'subject' => $title,
            'status' => mt_rand(1, 100) <= 98 ? 'sent' : 'failed',
            'error' => null, 'created_at' => $at, 'updated_at' => $at,
        ];
    }

    private function staffNotice(
        array &$notices, array &$mailLogs, int $hospitalId,
        string $type, string $title, string $body, ?string $link, string $at,
    ): void {
        $staff = User::where('hospital_id', $hospitalId)->where('is_active', true)
            ->whereIn('role', [User::ROLE_RECEPTIONIST, User::ROLE_ADMIN])->pluck('id');

        foreach ($staff as $uid) {
            if (mt_rand(1, 100) > 60) {
                continue; // not every staffer gets every fan-out in history
            }
            $nid = $this->take('notifications');
            $notices[] = [
                'id' => $nid, 'hospital_id' => $hospitalId, 'user_id' => $uid,
                'type' => $type, 'title' => $title, 'body' => $body, 'link' => $link,
                'read_at' => mt_rand(1, 100) <= 50 ? $at : null,
                'created_at' => $at, 'updated_at' => $at,
            ];
            $mailLogs[] = [
                'hospital_id' => $hospitalId, 'notification_id' => $nid, 'channel' => 'mail',
                'recipient' => 'user'.$uid.'@example.com', 'subject' => $title,
                'status' => 'sent', 'error' => null, 'created_at' => $at, 'updated_at' => $at,
            ];
        }
    }

    /**
     * @param  array<string,mixed>|null  $before
     * @param  array<string,mixed>|null  $after
     */
    private function audit(
        array &$audits, int $hospitalId, ?int $userId, string $action,
        string $subject, ?int $subjectId, ?array $before, ?array $after, string $at,
    ): void {
        $audits[] = [
            'hospital_id' => $hospitalId, 'user_id' => $userId, 'action' => $action,
            'subject_type' => $subject, 'subject_id' => $subjectId,
            'before' => $before !== null ? json_encode($before) : null,
            'after' => $after !== null ? json_encode($after) : null,
            'created_at' => $at, 'updated_at' => $at,
        ];
    }

    private function wipe(Hospital $hospital): void
    {
        NotificationLog::where('hospital_id', $hospital->id)->delete();
        Notification::where('hospital_id', $hospital->id)->delete();
        PaymentLog::where('hospital_id', $hospital->id)->delete();
        Payment::where('hospital_id', $hospital->id)->delete();
        QueueEntry::where('hospital_id', $hospital->id)->delete();
        QueueNumberSequence::where('hospital_id', $hospital->id)->delete();
        AuditLog::where('hospital_id', $hospital->id)->delete();
        Appointment::where('hospital_id', $hospital->id)->delete();
        AppointmentSlot::where('hospital_id', $hospital->id)->update(['booked_count' => 0]);
    }
}
