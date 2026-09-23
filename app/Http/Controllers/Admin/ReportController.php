<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ReportExport;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Practitioner;
use App\Models\QueueEntry;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Operational reports, readable in-dashboard and exportable as Excel
 * (PRD §17). Filterable by date range, department, and practitioner.
 */
class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $this->filters($request);

        return Inertia::render('Admin/Reports', [
            'filters' => $filters,
            'report' => $this->build($request->user()->hospital_id, $filters),
            'departments' => Department::forHospital($request->user()->hospital_id)->orderBy('name')->get(['id', 'name']),
            'practitioners' => Practitioner::forHospital($request->user()->hospital_id)->orderBy('full_name')->get(['id', 'full_name']),
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $filters = $this->filters($request);
        $report = $this->build($request->user()->hospital_id, $filters);

        $sheets = collect($report)->mapWithKeys(fn ($group, $name) => [
            $name => collect($group)->map(fn ($value, $metric) => [
                $metric, is_array($value) ? json_encode($value) : $value,
            ])->values()->all(),
        ])->all();

        return Excel::download(
            new ReportExport($sheets),
            "report-{$filters['from']}-{$filters['to']}.xlsx"
        );
    }

    public function exportPdf(Request $request)
    {
        $filters = $this->filters($request);
        $report = $this->build($request->user()->hospital_id, $filters);

        $pdf = Pdf::loadView('pdf.report', [
            'filters' => $filters,
            'report' => $report,
        ]);

        return $pdf->download("report-{$filters['from']}-{$filters['to']}.pdf");
    }

    /** @return array{from: string, to: string, department_id: ?int, practitioner_id: ?int} */
    private function filters(Request $request): array
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'department_id' => ['nullable', 'integer'],
            'practitioner_id' => ['nullable', 'integer'],
        ]);

        return [
            'from' => $validated['from'] ?? Carbon::now()->subDays(30)->toDateString(),
            'to' => $validated['to'] ?? Carbon::now()->toDateString(),
            'department_id' => isset($validated['department_id']) ? (int) $validated['department_id'] : null,
            'practitioner_id' => isset($validated['practitioner_id']) ? (int) $validated['practitioner_id'] : null,
        ];
    }

    /**
     * @param  array{from: string, to: string, department_id: ?int, practitioner_id: ?int}  $filters
     * @return array<string, mixed>
     */
    private function build(int $hospitalId, array $filters): array
    {
        $appointments = Appointment::forHospital($hospitalId)
            ->whereDate('scheduled_at', '>=', $filters['from'])
            ->whereDate('scheduled_at', '<=', $filters['to'])
            ->when($filters['department_id'], fn ($query, $id) => $query->where('department_id', $id))
            ->when($filters['practitioner_id'], fn ($query, $id) => $query->where('practitioner_id', $id))
            ->with('payment')
            ->limit(2000)
            ->get();

        $entries = QueueEntry::forHospital($hospitalId)
            ->whereDate('queue_date', '>=', $filters['from'])
            ->whereDate('queue_date', '<=', $filters['to'])
            ->when($filters['department_id'], fn ($query, $id) => $query->where('department_id', $id))
            ->when($filters['practitioner_id'], fn ($query, $id) => $query->where('practitioner_id', $id))
            ->with('practitioner:id,full_name')
            ->limit(2000)
            ->get();

        $payments = Payment::forHospital($hospitalId)
            ->whereDate('created_at', '>=', $filters['from'])
            ->whereDate('created_at', '<=', $filters['to'])
            ->limit(2000)
            ->get();

        $total = $appointments->count();

        $deptNames = Department::forHospital($hospitalId)->pluck('name', 'id');
        $userNames = User::where('hospital_id', $hospitalId)->pluck('name', 'id');

        return [
            'appointments' => [
                'total' => $total,
                'by_status' => $appointments->countBy('status')->all(),
                'rescheduled_share_pct' => $total > 0
                    ? round($appointments->where('reschedule_count', '>', 0)->count() / $total * 100, 1)
                    : 0,
                'avg_lead_hours' => round($appointments->avg(
                    fn ($a) => $a->created_at->diffInMinutes($a->scheduled_at, false) / 60
                ) ?? 0, 1),
                'cancel_reasons' => $appointments->whereNotNull('cancellation_reason')->countBy('cancellation_reason')->all(),
            ],
            'queue' => [
                'total' => $entries->count(),
                'completion_rate_pct' => $entries->count() > 0
                    ? round($entries->where('status', QueueEntry::STATUS_COMPLETED)->count() / $entries->count() * 100, 1)
                    : 0,
                'skipped' => $entries->where('status', QueueEntry::STATUS_SKIPPED)->count(),
                'recalled' => $entries->where('is_recalled', true)->count(),
                'avg_wait_minutes' => round($entries->whereNotNull('called_at')->avg(
                    fn ($e) => $e->created_at->diffInMinutes($e->called_at)
                ) ?? 0, 1),
                'avg_consult_minutes' => round($entries
                    ->whereNotNull('started_consultation_at')->whereNotNull('completed_at')->avg(
                        fn ($e) => $e->started_consultation_at->diffInMinutes($e->completed_at)
                    ) ?? 0, 1),
                'consult_by_practitioner' => $entries->whereNotNull('practitioner_id')
                    ->groupBy('practitioner.full_name')->map->count()->all(),
            ],
            'operational' => [
                'peak_hours' => $appointments->groupBy(fn ($a) => $a->scheduled_at->format('H:00'))->map->count()->sortKeys()->all(),
                'walk_in_share_pct' => $total > 0
                    ? round($appointments->where('is_walk_in', true)->count() / $total * 100, 1)
                    : 0,
                'no_show_rate_pct' => $total > 0
                    ? round($appointments->where('status', Appointment::STATUS_NO_SHOW)->count() / $total * 100, 1)
                    : 0,
                'avg_clearance_minutes' => $this->avgClearance($appointments),
            ],
            'payments' => [
                'online_success' => $payments->where('provider', Payment::PROVIDER_PAYSTACK)->where('status', Payment::STATUS_SUCCESS)->count(),
                'physical_collections' => $payments->where('provider', Payment::PROVIDER_MANUAL)->where('status', Payment::STATUS_SUCCESS)->count(),
                'failed' => $payments->whereIn('status', [Payment::STATUS_FAILED, Payment::STATUS_ABANDONED])->count(),
                'refunded' => $payments->where('status', Payment::STATUS_REFUNDED)->count(),
                'collected_ngn' => round($payments->where('status', Payment::STATUS_SUCCESS)->sum('amount_kobo') / 100, 2),
            ],
            'patients' => [
                'new_in_range' => Patient::forHospital($hospitalId)
                    ->whereDate('created_at', '>=', $filters['from'])
                    ->whereDate('created_at', '<=', $filters['to'])
                    ->count(),
                'returning_share_pct' => $this->returningShare($hospitalId, $filters),
                'volume_by_department' => $appointments->groupBy('department_id')
                    ->mapWithKeys(fn ($rows, $id) => [$deptNames[$id] ?? "Department #{$id}" => $rows->count()])
                    ->all(),
            ],
            'staff' => [
                'check_ins_by_staff' => $appointments->whereNotNull('checked_in_by')
                    ->countBy('checked_in_by')
                    ->mapWithKeys(fn ($n, $id) => [$userNames[$id] ?? "User #{$id}" => $n])
                    ->all(),
                'queue_actions_by_staff' => $entries->whereNotNull('action_by')
                    ->countBy('action_by')
                    ->mapWithKeys(fn ($n, $id) => [$userNames[$id] ?? "User #{$id}" => $n])
                    ->all(),
            ],
        ];
    }

    /** @param Collection<int, Appointment> $appointments */
    private function avgClearance($appointments): float
    {
        // Desk clearance only: time from check-in to the manual payment.
        // Pre-paid online visits never queued at a desk, so they are out.
        $minutes = $appointments
            ->whereNotNull('checked_in_at')
            ->map(fn ($a) => [$a, $a->payment])
            ->filter(fn ($pair) => $pair[1] !== null
                && $pair[1]->provider === Payment::PROVIDER_MANUAL
                && $pair[1]->paid_at !== null)
            ->map(fn ($pair) => max(0, $pair[0]->checked_in_at->diffInMinutes($pair[1]->paid_at, false)));

        return round($minutes->avg() ?? 0, 1);
    }

    /** @param array{from: string, to: string, department_id: ?int, practitioner_id: ?int} $filters */
    private function returningShare(int $hospitalId, array $filters): float
    {
        $patientIds = Appointment::forHospital($hospitalId)
            ->whereDate('scheduled_at', '>=', $filters['from'])
            ->whereDate('scheduled_at', '<=', $filters['to'])
            ->pluck('patient_id')
            ->unique();

        if ($patientIds->isEmpty()) {
            return 0.0;
        }

        $repeaters = Appointment::forHospital($hospitalId)
            ->whereIn('patient_id', $patientIds)
            ->selectRaw('patient_id, COUNT(*) as total')
            ->groupBy('patient_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        return round($repeaters / $patientIds->count() * 100, 1);
    }
}
