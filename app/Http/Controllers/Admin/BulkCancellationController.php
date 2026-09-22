<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\Practitioner;
use App\Models\Setting;
use App\Models\User;
use App\Services\BookingService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Bulk day cancellation (e.g. practitioner sick day, PRD §13/§14).
 * Optionally moves bookings to a replacement practitioner where they
 * hold a free slot the same day; everything else is cancelled with
 * auto-refund for online payments.
 */
class BulkCancellationController extends Controller
{
    public function create(Request $request): Response
    {
        $validated = $request->validate([
            'practitioner_id' => ['nullable', 'integer'],
            'department_id' => ['nullable', 'integer'],
            'date' => ['nullable', 'date'],
        ]);

        $preview = null;
        if (! empty($validated['practitioner_id']) && ! empty($validated['date'])) {
            $preview = $this->preview(
                (int) $validated['practitioner_id'],
                isset($validated['department_id']) ? (int) $validated['department_id'] : null,
                $validated['date']
            );
        }

        return Inertia::render('Admin/BulkCancellation', [
            'filters' => $validated,
            'preview' => $preview,
            'practitioners' => Practitioner::orderBy('full_name')->get(['id', 'full_name']),
        ]);
    }

    public function store(Request $request, BookingService $bookings): RedirectResponse
    {
        $validated = $request->validate([
            'practitioner_id' => ['required', 'integer', Rule::exists('practitioners', 'id')],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
            'date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:255'],
            'replacement_practitioner_id' => ['nullable', 'integer', Rule::exists('practitioners', 'id')],
        ]);

        $moved = 0;
        $cancelled = 0;

        foreach ($this->affected($validated['practitioner_id'], $validated['department_id'] ?? null, $validated['date']) as $appointment) {
            $replacementSlot = null;

            if (! empty($validated['replacement_practitioner_id'])) {
                $replacementSlot = $this->replacementSlot(
                    (int) $validated['replacement_practitioner_id'],
                    $appointment->department_id,
                    $validated['date']
                );
            }

            if ($replacementSlot !== null) {
                $bookings->reschedule($appointment, $replacementSlot);
                $moved++;
            } else {
                $bookings->cancel($appointment, $validated['reason'], $request->user());
                $cancelled++;
            }
        }

        app(NotificationService::class)->sendToStaff(
            $request->user()->hospital_id,
            [User::ROLE_RECEPTIONIST, User::ROLE_ADMIN],
            'bulk_cancellation',
            'Bulk cancellation completed',
            "{$moved} moved, {$cancelled} cancelled for {$validated['date']}. Reason: {$validated['reason']}",
            '/staff/appointments'
        );

        return redirect('/admin/bulk-cancellation')->with(
            'status', "Bulk action done: {$moved} moved to replacement, {$cancelled} cancelled."
        );
    }

    /** @return array{total: int, paid: int} */
    private function preview(int $practitionerId, ?int $departmentId, string $date): array
    {
        $affected = $this->affected($practitionerId, $departmentId, $date);

        return [
            'total' => $affected->count(),
            'paid' => $affected->where('payment_status', Appointment::PAY_PAID)->count(),
        ];
    }

    /** @return Collection<int, Appointment> */
    private function affected(int $practitionerId, ?int $departmentId, string $date)
    {
        return Appointment::where('practitioner_id', $practitionerId)
            ->when($departmentId !== null, fn ($query) => $query->where('department_id', $departmentId))
            ->whereDate('scheduled_at', $date)
            ->where('status', Appointment::STATUS_SCHEDULED)
            ->get();
    }

    private function replacementSlot(int $replacementId, int $departmentId, string $date): ?AppointmentSlot
    {
        $replacement = Practitioner::find($replacementId);

        if ($replacement === null || ! $replacement->isBookable()) {
            return null;
        }

        if (! $replacement->departments()->where('departments.id', $departmentId)->exists()) {
            return null;
        }

        return AppointmentSlot::where('practitioner_id', $replacementId)
            ->where('department_id', $departmentId)
            ->whereDate('date', $date)
            ->where('is_active', true)
            ->whereRaw('booked_count < capacity')
            ->where('starts_at', '>', Carbon::now()->addMinutes(
                (int) (Setting::get($replacement->hospital_id, Setting::BOOKING_CUTOFF_MINUTES) ?? 120)
            ))
            ->orderBy('starts_at')
            ->first();
    }
}
