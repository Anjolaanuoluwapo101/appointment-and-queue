<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\PractitionerSchedule;
use App\Models\QueueEntry;
use App\Models\User;
use App\Services\QueueService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Receptionist queue desk (PRD §9, §12): check-in, clearance, walk-ins,
 * and the full set of queue operations on the department dashboard.
 */
class QueueController extends Controller
{
    public function dashboard(Request $request, QueueService $queue): Response
    {
        $department = $this->department($request);

        return Inertia::render('Staff/Queue/Dashboard', [
            'departments' => Department::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'department_id' => $department?->id,
            'snapshot' => $department !== null ? $queue->snapshot($department) : null,
        ]);
    }

    /**
     * Practitioner board: today's schedule plus the shared department
     * queue filtered to their own patients (PRD §5 + §9 decision).
     */
    public function practitionerBoard(Request $request, QueueService $queue): Response
    {
        $practitioner = $request->user()->practitioner;

        abort_unless($practitioner !== null, 404, 'No practitioner profile linked.');

        $department = $this->department($request);

        if ($department !== null && ! $practitioner->departments()->where('departments.id', $department->id)->exists()) {
            $department = null;
        }

        $department ??= $practitioner->departments()->orderBy('departments.id')->first();

        $today = Carbon::now()->startOfDay();

        return Inertia::render('Practitioner/Queue', [
            'practitioner_id' => $practitioner->id,
            'departments' => $practitioner->departments()->orderBy('name')->get(['departments.id', 'name']),
            'department_id' => $department?->id,
            'schedule' => $department !== null ? PractitionerSchedule::where('practitioner_id', $practitioner->id)
                ->where('department_id', $department->id)
                ->where('weekday', (int) $today->format('w'))
                ->where('is_active', true)
                ->get() : [],
            'snapshot' => $department !== null ? $queue->snapshot($department) : null,
        ]);
    }

    public function checkIn(Appointment $appointment, QueueService $queue): RedirectResponse
    {
        $this->authorize('update', $appointment);

        $queue->checkIn($appointment, request()->user());

        $fresh = $appointment->fresh();

        return back()->with('status', match ($fresh->status) {
            Appointment::STATUS_IN_QUEUE => 'Checked in and queued.',
            default => 'Checked in. Awaiting payment clearance.',
        });
    }

    public function clear(Request $request, Appointment $appointment, QueueService $queue): RedirectResponse
    {
        $this->authorize('update', $appointment);

        $validated = $request->validate([
            'receipt_no' => ['required', 'string', 'max:64'],
            'method' => ['required', 'string', 'max:32'],
        ]);

        $queue->clearManually($appointment, $validated['receipt_no'], $validated['method'], $request->user());

        return back()->with('status', 'Payment cleared. Patient queued.');
    }

    public function walkInForm(): Response
    {
        return Inertia::render('Staff/Queue/WalkIn', [
            'departments' => Department::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function walkIn(Request $request, QueueService $queue): RedirectResponse
    {
        $validated = $request->validate([
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'practitioner_id' => ['nullable', 'integer', 'exists:practitioners,id'],
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:32'],
            'receipt_no' => ['required', 'string', 'max:64'],
            'method' => ['required', 'string', 'max:32'],
        ]);

        $entry = $queue->walkIn(
            Department::findOrFail($validated['department_id']),
            $validated['practitioner_id'] ?? null,
            ['full_name' => $validated['full_name'], 'phone' => $validated['phone']],
            $validated['receipt_no'],
            $validated['method'],
            $request->user()
        );

        return redirect("/staff/queue?department_id={$entry->department_id}")
            ->with('status', "Walk-in queued as {$entry->queue_number}.");
    }

    public function callNext(Request $request, QueueService $queue): RedirectResponse
    {
        $department = $this->department($request);

        if ($department === null) {
            return back()->withErrors(['queue' => 'Choose a department first.']);
        }

        $entry = $queue->callNext($department, $this->actingPractitionerId($request), $request->user());

        return back()->with('status', $entry !== null ? "Called {$entry->queue_number}." : 'Nobody waiting.');
    }

    public function call(QueueEntry $entry, QueueService $queue): RedirectResponse
    {
        $this->ensurePractitionerScope($entry);
        $queue->call($entry, request()->user());

        return back()->with('status', "Called {$entry->fresh()->queue_number}.");
    }

    public function begin(QueueEntry $entry, QueueService $queue): RedirectResponse
    {
        $this->ensurePractitionerScope($entry);
        $queue->beginConsultation($entry, request()->user());

        return back()->with('status', 'Consultation started.');
    }

    public function skip(QueueEntry $entry, QueueService $queue): RedirectResponse
    {
        $this->ensurePractitionerScope($entry);
        $queue->skip($entry, request()->user());

        return back()->with('status', 'Patient skipped. They stay visible for recall.');
    }

    public function recall(QueueEntry $entry, QueueService $queue): RedirectResponse
    {
        $this->ensurePractitionerScope($entry);
        $queue->recall($entry, request()->user());

        return back()->with('status', 'Patient recalled to next position.');
    }

    public function complete(QueueEntry $entry, QueueService $queue): RedirectResponse
    {
        $this->ensurePractitionerScope($entry);
        $queue->complete($entry, request()->user());

        return back()->with('status', 'Consultation completed.');
    }

    public function cancelEntry(Request $request, QueueEntry $entry, QueueService $queue): RedirectResponse
    {
        $this->ensurePractitionerScope($entry);

        $validated = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);

        $queue->cancelEntry($entry, $validated['reason'] ?? null, $request->user());

        return back()->with('status', 'Queue entry cancelled. It stays visible with the appointment intact.');
    }

    private function department(Request $request): ?Department
    {
        $id = $request->query('department_id');

        return is_numeric($id) ? Department::find((int) $id) : Department::where('is_active', true)->orderBy('id')->first();
    }

    private function actingPractitionerId(Request $request): ?int
    {
        $user = $request->user();

        if ($user->role === User::ROLE_PRACTITIONER) {
            return $user->practitioner?->id;
        }

        $id = $request->query('practitioner_id') ?? $request->input('practitioner_id');

        return is_numeric($id) ? (int) $id : null;
    }

    private function ensurePractitionerScope(QueueEntry $entry): void
    {
        $user = request()->user();

        if ($user->role === User::ROLE_PRACTITIONER
            && $entry->practitioner_id !== $user->practitioner?->id
        ) {
            abort(403);
        }
    }
}
