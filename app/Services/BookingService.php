<?php

namespace App\Services;

use App\Jobs\ProcessRefund;
use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Patient;
use App\Models\Practitioner;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Booking with capacity-safe slot holds (PRD §7, §26).
 * Slot capacity is claimed with an atomic conditional increment so
 * concurrent bookings can never overfill a slot. Emits MVP notices
 * and audit entries for every state change.
 */
class BookingService
{
    public function __construct(private FeeResolver $fees, private NotificationService $notices) {}

    /**
     * @throws ValidationException
     */
    public function book(
        Patient $patient,
        Department $department,
        ?Practitioner $practitioner,
        AppointmentSlot $slot,
        string $paymentMode,
        bool $isWalkIn = false,
    ): Appointment {
        $this->assertBookable($patient, $department, $practitioner, $slot, $paymentMode);

        $created = DB::transaction(function () use ($patient, $department, $practitioner, $slot, $paymentMode, $isWalkIn): Appointment {
            $claimed = AppointmentSlot::where('id', $slot->id)
                ->where('is_active', true)
                ->whereRaw('booked_count < capacity')
                ->increment('booked_count');

            if ($claimed === 0) {
                throw ValidationException::withMessages(['slot' => 'This slot just filled up. Please choose another.']);
            }

            return Appointment::create([
                'hospital_id' => $patient->hospital_id,
                'patient_id' => $patient->id,
                'practitioner_id' => $practitioner?->id ?? $slot->practitioner_id,
                'department_id' => $department->id,
                'slot_id' => $slot->id,
                'scheduled_at' => $slot->starts_at,
                'status' => Appointment::STATUS_SCHEDULED,
                'payment_mode' => $paymentMode,
                'payment_status' => Appointment::PAY_UNPAID,
                'is_walk_in' => $isWalkIn,
            ]);
        });

        $when = $created->scheduled_at->format('D d M H:i');
        $this->notices->send(
            $patient->user, $patient->hospital_id, 'appointment_confirmed',
            'Appointment confirmed',
            "{$department->name} on {$when}.",
            "/patient/appointments/{$created->id}"
        );
        $this->notices->sendToStaff(
            $patient->hospital_id, [User::ROLE_RECEPTIONIST, User::ROLE_ADMIN], 'appointment_booked',
            'New appointment booked',
            "{$patient->full_name} — {$department->name} on {$when}.",
            '/staff/appointments'
        );
        AuditLog::record($patient->hospital_id, null, 'appointment_booked', $created);

        return $created;
    }

    /**
     * Moves to a new slot in the same department. The practitioner follows
     * the new slot, which is what makes replacement moves work.
     *
     * @throws ValidationException
     */
    public function reschedule(Appointment $appointment, AppointmentSlot $newSlot): Appointment
    {
        if (! in_array($appointment->status, [Appointment::STATUS_SCHEDULED], true)) {
            throw ValidationException::withMessages(['appointment' => 'Only scheduled appointments can be rescheduled.']);
        }

        if ($newSlot->department_id !== $appointment->department_id) {
            throw ValidationException::withMessages(['slot' => 'Rescheduling stays within the same department. Cancel and rebook to switch departments.']);
        }

        $department = $appointment->department;
        $this->assertSlotFor($appointment->patient, $department, $newSlot->practitioner, $newSlot);

        $oldSlotId = $appointment->slot_id;

        $fresh = DB::transaction(function () use ($appointment, $newSlot, $oldSlotId): Appointment {
            $claimed = AppointmentSlot::where('id', $newSlot->id)
                ->where('is_active', true)
                ->whereRaw('booked_count < capacity')
                ->increment('booked_count');

            if ($claimed === 0) {
                throw ValidationException::withMessages(['slot' => 'This slot just filled up. Please choose another.']);
            }

            if ($oldSlotId !== null) {
                AppointmentSlot::where('id', $oldSlotId)->where('booked_count', '>', 0)->decrement('booked_count');
            }

            $appointment->update([
                'slot_id' => $newSlot->id,
                'practitioner_id' => $newSlot->practitioner_id,
                'scheduled_at' => $newSlot->starts_at,
                'reschedule_count' => $appointment->reschedule_count + 1,
            ]);

            return $appointment->fresh();
        });

        $when = $fresh->scheduled_at->format('D d M H:i');
        $patient = $fresh->patient;
        $this->notices->send(
            $patient->user, $fresh->hospital_id, 'appointment_rescheduled',
            'Appointment rescheduled',
            "Moved to {$when}. Your payment carries over.",
            "/patient/appointments/{$fresh->id}"
        );
        AuditLog::record($fresh->hospital_id, null, 'appointment_rescheduled', $fresh,
            ['slot_id' => $oldSlotId], ['slot_id' => $fresh->slot_id]);

        return $fresh;
    }

    public function cancel(Appointment $appointment, ?string $reason = null, ?User $actor = null): Appointment
    {
        if (! $appointment->isCancellable()) {
            throw ValidationException::withMessages(['appointment' => 'Only scheduled appointments can be cancelled.']);
        }

        $fresh = DB::transaction(function () use ($appointment, $reason): Appointment {
            if ($appointment->slot_id !== null && $appointment->status === Appointment::STATUS_SCHEDULED) {
                AppointmentSlot::where('id', $appointment->slot_id)->where('booked_count', '>', 0)->decrement('booked_count');
            }

            $appointment->update([
                'status' => Appointment::STATUS_CANCELLED,
                'cancellation_reason' => $reason,
            ]);

            if ($appointment->isPaid()) {
                ProcessRefund::dispatch($appointment->fresh()->id);
            }

            return $appointment->fresh();
        });

        $patient = $fresh->patient;
        $link = "/patient/appointments/{$fresh->id}";

        if ($actor !== null && $actor->role === User::ROLE_PATIENT) {
            $this->notices->sendToStaff(
                $fresh->hospital_id, [User::ROLE_RECEPTIONIST, User::ROLE_ADMIN], 'appointment_cancelled',
                'Appointment cancelled by patient',
                "{$patient->full_name} cancelled {$fresh->scheduled_at->format('D d M H:i')}.",
                '/staff/appointments'
            );
        } else {
            $this->notices->send(
                $patient->user, $fresh->hospital_id, 'appointment_cancelled',
                'Appointment cancelled',
                $fresh->isPaid()
                    ? 'Cancelled. Your online payment will be refunded automatically.'
                    : 'Your appointment was cancelled.',
                $link
            );
        }

        AuditLog::record($fresh->hospital_id, $actor?->id, 'appointment_cancelled', $fresh,
            ['status' => Appointment::STATUS_SCHEDULED], ['status' => Appointment::STATUS_CANCELLED]);

        return $fresh;
    }

    public function markNoShow(Appointment $appointment, ?User $actor = null): Appointment
    {
        if ($appointment->status !== Appointment::STATUS_SCHEDULED) {
            throw ValidationException::withMessages(['appointment' => 'Only scheduled appointments can be marked as no-show.']);
        }

        $appointment->update(['status' => Appointment::STATUS_NO_SHOW]);

        AuditLog::record($appointment->hospital_id, $actor?->id, 'appointment_no_show', $appointment,
            ['status' => Appointment::STATUS_SCHEDULED], ['status' => Appointment::STATUS_NO_SHOW]);

        return $appointment->fresh();
    }

    /**
     * @throws ValidationException
     */
    private function assertBookable(
        Patient $patient,
        Department $department,
        ?Practitioner $practitioner,
        AppointmentSlot $slot,
        string $paymentMode,
    ): void {
        if (! $department->is_active) {
            throw ValidationException::withMessages(['department' => 'This department is not currently accepting bookings.']);
        }

        if ($practitioner !== null) {
            if (! $practitioner->isBookable()) {
                throw ValidationException::withMessages(['practitioner' => 'This practitioner is not available for booking.']);
            }
            if (! $practitioner->departments()->where('departments.id', $department->id)->exists()) {
                throw ValidationException::withMessages(['practitioner' => 'Practitioner does not serve this department.']);
            }
        }

        if (($department->payment_mode === Department::PAYMENT_ONLINE_REQUIRED && $paymentMode !== Appointment::PAY_MODE_ONLINE)
            || ($department->payment_mode === Department::PAYMENT_PHYSICAL_ONLY && $paymentMode !== Appointment::PAY_MODE_PHYSICAL)
        ) {
            throw ValidationException::withMessages(['payment_mode' => 'This department does not offer the chosen payment method.']);
        }

        $this->assertSlotFor($patient, $department, $practitioner, $slot);
    }

    /**
     * @throws ValidationException
     */
    private function assertSlotFor(
        Patient $patient,
        Department $department,
        ?Practitioner $practitioner,
        AppointmentSlot $slot,
    ): void {
        if (! $slot->is_active || $slot->isFull()) {
            throw ValidationException::withMessages(['slot' => 'This slot is no longer available.']);
        }

        if ($slot->department_id !== $department->id || $slot->hospital_id !== $patient->hospital_id) {
            throw ValidationException::withMessages(['slot' => 'This slot does not belong to the chosen department.']);
        }

        if ($practitioner !== null && $slot->practitioner_id !== $practitioner->id) {
            throw ValidationException::withMessages(['slot' => 'This slot belongs to a different practitioner.']);
        }

        $cutoff = (int) (Setting::get($patient->hospital_id, Setting::BOOKING_CUTOFF_MINUTES) ?? 120);
        if ($slot->starts_at->lt(Carbon::now()->addMinutes($cutoff))) {
            throw ValidationException::withMessages(['slot' => 'Booking closed for this slot (cutoff reached).']);
        }
    }
}
