<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Notification;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Patient home (PRD §15): upcoming appointment, history link,
 * realtime queue entry point, unread notices.
 */
class DashboardController extends Controller
{
    public function show(Request $request): Response
    {
        $patient = $request->user()->patient()->firstOrFail();

        return Inertia::render('Patient/Dashboard', [
            'upcoming' => Appointment::where('patient_id', $patient->id)
                ->whereIn('status', [Appointment::STATUS_SCHEDULED, Appointment::STATUS_PENDING_CLEARANCE, Appointment::STATUS_IN_QUEUE])
                ->with(['department:id,name', 'practitioner:id,full_name'])
                ->orderBy('scheduled_at')
                ->limit(3)
                ->get(),
            'unread' => Notification::where('user_id', $request->user()->id)
                ->whereNull('read_at')
                ->orderByDesc('id')
                ->limit(5)
                ->get(['id', 'type', 'title', 'body', 'link', 'created_at']),
        ]);
    }
}
