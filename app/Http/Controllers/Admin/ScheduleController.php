<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppointmentSlot;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Practitioner;
use App\Models\PractitionerSchedule;
use App\Services\NotificationService;
use App\Services\SlotGenerator;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Practitioner schedule management (PRD §8), including the modification
 * blocking policy: changes are refused while future slots from the
 * schedule still hold bookings.
 */
class ScheduleController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Schedules/Index', [
            'schedules' => PractitionerSchedule::with(['practitioner:id,full_name', 'department:id,name'])
                ->orderBy('weekday')
                ->orderBy('start_time')
                ->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Schedules/Create', $this->formData());
    }

    public function store(Request $request, SlotGenerator $generator): RedirectResponse
    {
        $validated = $this->validated($request);
        $this->assertDepartmentLink($validated);

        $schedule = PractitionerSchedule::create($validated + ['hospital_id' => $request->user()->hospital_id]);
        $generator->generateForHospital($schedule->hospital);

        AuditLog::record($schedule->hospital_id, $request->user()->id, 'schedule_created', $schedule);
        $this->notifyPractitioner($schedule, 'Schedule created', $request->user()->hospital_id);

        return redirect('/admin/schedules')->with('status', 'Schedule created and slots generated.');
    }

    public function edit(PractitionerSchedule $schedule): Response
    {
        return Inertia::render('Admin/Schedules/Edit', [
            'schedule' => $schedule,
            ...$this->formData(),
        ]);
    }

    public function update(Request $request, PractitionerSchedule $schedule, SlotGenerator $generator): RedirectResponse
    {
        $validated = $this->validated($request, $schedule->id);

        if ((int) $validated['practitioner_id'] !== $schedule->practitioner_id
            || (int) $validated['department_id'] !== $schedule->department_id
        ) {
            return back()->withErrors(['schedule' => 'Practitioner and department cannot be changed. Create a new schedule instead.']);
        }

        $this->assertDepartmentLink($validated);

        $conflicts = $this->bookedFutureSlots($schedule);
        if ($conflicts->isNotEmpty()) {
            return back()->withErrors([
                'schedule' => 'Blocked: '.$conflicts->count().' booked slot(s) fall in this schedule (e.g. '
                    .$conflicts->first()->starts_at->format('D d M H:i').'). Cancel or reschedule each booking first.',
            ]);
        }

        AppointmentSlot::where('schedule_id', $schedule->id)
            ->whereDate('date', '>=', Carbon::today()->toDateString())
            ->where('booked_count', 0)
            ->delete();

        $schedule->update($validated);
        $generator->generateForHospital($schedule->hospital);

        AuditLog::record($schedule->hospital_id, $request->user()->id, 'schedule_updated', $schedule);
        $this->notifyPractitioner($schedule->fresh(), 'Schedule modified by admin', $request->user()->hospital_id);

        return redirect('/admin/schedules')->with('status', 'Schedule updated and future slots regenerated.');
    }

    public function destroy(PractitionerSchedule $schedule): RedirectResponse
    {
        $conflicts = $this->bookedFutureSlots($schedule);
        if ($conflicts->isNotEmpty()) {
            return back()->withErrors([
                'schedule' => 'Blocked: '.$conflicts->count().' booked slot(s) still use this schedule. Cancel or reschedule each booking first.',
            ]);
        }

        AppointmentSlot::where('schedule_id', $schedule->id)
            ->whereDate('date', '>=', Carbon::today()->toDateString())
            ->delete();

        $schedule->delete();

        AuditLog::record($request->user()->hospital_id, $request->user()->id, 'schedule_deleted', $schedule);

        return redirect('/admin/schedules')->with('status', 'Schedule deleted.');
    }

    /** @return Collection<int, AppointmentSlot> */
    private function bookedFutureSlots(PractitionerSchedule $schedule)
    {
        return AppointmentSlot::where('schedule_id', $schedule->id)
            ->whereDate('date', '>=', Carbon::today()->toDateString())
            ->where('booked_count', '>', 0)
            ->orderBy('starts_at')
            ->limit(50)
            ->get();
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $hospitalId = $request->user()->hospital_id;

        $validated = $request->validate([
            'practitioner_id' => ['required', 'integer', Rule::exists('practitioners', 'id')->where('hospital_id', $hospitalId)],
            'department_id' => ['required', 'integer', Rule::exists('departments', 'id')->where('hospital_id', $hospitalId)],
            'weekday' => ['required', 'integer', 'min:0', 'max:6'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'slot_duration_minutes' => ['required', 'integer', 'min:5', 'max:480'],
            'max_per_slot' => ['required', 'integer', 'min:1', 'max:100'],
            'break_start' => ['nullable', 'date_format:H:i'],
            'break_end' => ['nullable', 'date_format:H:i', 'after:break_start'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $overlap = PractitionerSchedule::where('practitioner_id', $validated['practitioner_id'])
            ->where('department_id', $validated['department_id'])
            ->where('weekday', $validated['weekday'])
            ->where('is_active', true)
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('start_time', '<', $validated['end_time'])
            ->where('end_time', '>', $validated['start_time'])
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'schedule' => 'Overlaps an existing active schedule for this practitioner, department and day.',
            ]);
        }

        return $validated;
    }

    private function assertDepartmentLink(array $validated): void
    {
        $linked = Practitioner::where('id', $validated['practitioner_id'])
            ->whereHas('departments', fn ($query) => $query->where('departments.id', $validated['department_id']))
            ->exists();

        if (! $linked) {
            throw ValidationException::withMessages([
                'department_id' => 'Practitioner does not belong to this department.',
            ]);
        }
    }

    private function notifyPractitioner(PractitionerSchedule $schedule, string $title, int $hospitalId): void
    {
        $user = $schedule->practitioner?->user;

        if ($user === null) {
            return;
        }

        app(NotificationService::class)->send(
            $user, $hospitalId, 'schedule_modified',
            $title,
            "Your {$schedule->department->name} schedule was changed by an admin.",
            '/staff/my-queue'
        );
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        return [
            'practitioners' => Practitioner::with('departments:id,name')->orderBy('full_name')->get(['id', 'full_name']),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'weekdays' => ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
        ];
    }
}
