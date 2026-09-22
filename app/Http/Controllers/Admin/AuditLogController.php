<?php

namespace App\Http\Controllers\Admin;

use App\Exports\AuditExport;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Audit trail viewer (PRD §19): admin-only read with filters and
 * Excel export for compliance and incident investigation.
 */
class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'action' => ['nullable', 'string'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        return Inertia::render('Admin/AuditLog', [
            'filters' => $validated,
            'entries' => $this->query($request->user()->hospital_id, $validated)
                ->with('user:id,name')
                ->orderByDesc('id')
                ->limit(200)
                ->get(),
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $validated = $request->validate([
            'action' => ['nullable', 'string'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $rows = $this->query($request->user()->hospital_id, $validated)
            ->with('user:id,name')
            ->orderByDesc('id')
            ->limit(2000)
            ->get()
            ->map(fn ($log) => [
                $log->created_at->toDateTimeString(),
                $log->user?->name ?? 'system',
                $log->action,
                $log->subject_type !== null ? class_basename($log->subject_type).'#'.$log->subject_id : '—',
                json_encode($log->before),
                json_encode($log->after),
            ])->all();

        return Excel::download(
            new AuditExport($rows),
            'audit-log.xlsx'
        );
    }

    public function exportPdf(Request $request)
    {
        $validated = $request->validate([
            'action' => ['nullable', 'string'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $entries = $this->query($request->user()->hospital_id, $validated)
            ->with('user:id,name')
            ->orderByDesc('id')
            ->limit(2000)
            ->get();

        $pdf = Pdf::loadView('pdf.audit-log', [
            'entries' => $entries,
        ]);

        return $pdf->download('audit-log.pdf');
    }

    /** @param array<string, mixed> $filters */
    private function query(int $hospitalId, array $filters)
    {
        return AuditLog::forHospital($hospitalId)
            ->when($filters['action'] ?? null, fn ($query, $action) => $query->where('action', $action))
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('created_at', '<=', $to));
    }
}
