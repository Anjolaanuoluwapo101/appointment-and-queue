<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationLogController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        return Inertia::render('Admin/NotificationLogs', [
            'filters' => $validated,
            'logs' => $this->query($request->user()->hospital_id, $validated)
                ->orderByDesc('id')
                ->limit(200)
                ->get(),
        ]);
    }

    /** @param array<string, mixed> $filters */
    private function query(int $hospitalId, array $filters)
    {
        return NotificationLog::forHospital($hospitalId)
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('created_at', '<=', $to));
    }
}
