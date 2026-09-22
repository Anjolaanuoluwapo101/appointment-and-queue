<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

/**
 * Patients manage only their own appointments (PRD §5, §18).
 * Staff act within their own hospital.
 */
class AppointmentPolicy
{
    public function view(User $user, Appointment $appointment): bool
    {
        return $this->canAccess($user, $appointment);
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return $this->canAccess($user, $appointment);
    }

    private function canAccess(User $user, Appointment $appointment): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->role === User::ROLE_PATIENT) {
            return $appointment->patient?->user_id === $user->id;
        }

        return $user->isStaff() && $user->hospital_id === $appointment->hospital_id;
    }
}
