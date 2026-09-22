<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;

/**
 * Patients see only themselves (PRD §5, §18).
 * Staff act within their own hospital.
 */
class PatientPolicy
{
    public function view(User $user, Patient $patient): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->role === User::ROLE_PATIENT) {
            return $patient->user_id === $user->id;
        }

        return $user->isStaff() && $user->hospital_id === $patient->hospital_id;
    }

    public function update(User $user, Patient $patient): bool
    {
        return $this->view($user, $patient);
    }
}
