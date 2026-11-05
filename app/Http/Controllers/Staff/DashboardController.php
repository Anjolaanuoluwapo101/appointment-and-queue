<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\QueueEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Receptionist home (PRD §15): today's counts across the whole flow —
 * pending check-in, pending clearance, waiting, in consultation,
 * completed, no-shows, cancelled.
 */
class DashboardController extends Controller
{
    public function show(Request $request): Response
    {
        $user = $request->user();
        $hospitalId = $user->hospital_id;
        $today = Carbon::today()->toDateString();

        // Practitioners see only their own departments' counts.
        $ownDeptIds = null;
        if ($user->role === User::ROLE_PRACTITIONER) {
            $practitioner = $user->loadMissing('practitioner')->practitioner;
            $ownDeptIds = $practitioner !== null
                ? $practitioner->departments()->pluck('departments.id')
                : collect();
        }

        $appointmentCounts = Appointment::forHospital($hospitalId)
            ->whereDate('scheduled_at', $today)
            ->when($ownDeptIds !== null, fn ($query) => $query->whereIn('department_id', $ownDeptIds))
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $queueCounts = QueueEntry::forHospital($hospitalId)
            ->whereDate('queue_date', $today)
            ->when($ownDeptIds !== null, fn ($query) => $query->whereIn('department_id', $ownDeptIds))
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return Inertia::render('Staff/Dashboard', [
            'today' => $today,
            'appointments' => $appointmentCounts,
            'queue' => $queueCounts,
        ]);
    }
}
