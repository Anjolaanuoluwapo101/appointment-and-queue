<?php

use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Public department queue channel: numbers only, no names beyond the
// snapshot's waiting list (same data as the waiting-room display).
Broadcast::channel('queue.{departmentId}', function () {
    return true;
});

// Private patient channel: the patient themselves, or staff of the
// patient's hospital.
Broadcast::channel('patient.{patientId}', function (User $user, int $patientId) {
    $patient = Patient::find($patientId);

    if ($patient === null || ! $user->is_active) {
        return false;
    }

    if ($user->role === User::ROLE_PATIENT) {
        return $patient->user_id === $user->id;
    }

    return $user->isStaff() && $user->hospital_id === $patient->hospital_id;
});
