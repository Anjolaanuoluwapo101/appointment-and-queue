<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Payment;
use App\Models\PaymentLog;
use App\Models\QueueEntry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Hospital admin home (PRD §15): operational stats, payment summary
 * with refund-failure inbox, and links to management areas.
 */
class DashboardController extends Controller
{
    public function show(Request $request): Response
    {
        $hospitalId = $request->user()->hospital_id;
        $today = Carbon::today()->toDateString();

        return Inertia::render('Admin/Dashboard', [
            'today' => $today,
            'appointments_today' => Appointment::forHospital($hospitalId)->whereDate('scheduled_at', $today)->count(),
            'queue_active_today' => QueueEntry::forHospital($hospitalId)
                ->whereDate('queue_date', $today)
                ->whereIn('status', [QueueEntry::STATUS_WAITING, QueueEntry::STATUS_CALLED, QueueEntry::STATUS_SKIPPED])
                ->count(),
            'no_show_rate_30d' => $this->rate($hospitalId, Appointment::STATUS_NO_SHOW),
            'cancel_rate_30d' => $this->rate($hospitalId, Appointment::STATUS_CANCELLED),
            'payments' => Payment::forHospital($hospitalId)
                ->selectRaw('status, COUNT(*) as total, COALESCE(SUM(amount_kobo), 0) as kobo')
                ->groupBy('status')
                ->get(),
            'refund_failures' => PaymentLog::forHospital($hospitalId)
                ->where('event', 'refund_failed')
                ->orderByDesc('id')
                ->limit(20)
                ->get(['id', 'payload', 'created_at']),
        ]);
    }

    private function rate(int $hospitalId, string $status): float
    {
        $total = Appointment::forHospital($hospitalId)
            ->where('scheduled_at', '>=', Carbon::now()->subDays(30))
            ->count();

        if ($total === 0) {
            return 0.0;
        }

        $matching = Appointment::forHospital($hospitalId)
            ->where('scheduled_at', '>=', Carbon::now()->subDays(30))
            ->where('status', $status)
            ->count();

        return round($matching / $total * 100, 1);
    }
}
