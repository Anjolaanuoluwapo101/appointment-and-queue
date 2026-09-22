<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\Department;
use App\Models\Patient;
use App\Models\Practitioner;
use App\Models\Setting;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Receptionist appointment desk (PRD §7): daily view, book on behalf,
 * modify, cancel, no-show. Filter by date, department, practitioner,
 * status.
 */
class AppointmentController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date'],
            'department_id' => ['nullable', 'integer'],
            'practitioner_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'string'],
        ]);

        $appointments = Appointment::with(['patient:id,full_name,phone', 'department:id,name', 'practitioner:id,full_name'])
            ->when($validated['date'] ?? null,
                fn ($query, $date) => $query->whereDate('scheduled_at', $date),
                fn ($query) => $query->whereDate('scheduled_at', Carbon::today()->toDateString()))
            ->when($validated['department_id'] ?? null, fn ($query, $id) => $query->where('department_id', $id))
            ->when($validated['practitioner_id'] ?? null, fn ($query, $id) => $query->where('practitioner_id', $id))
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderBy('scheduled_at')
            ->limit(500)
            ->get();

        return Inertia::render('Staff/Appointments/Index', [
            'appointments' => $appointments,
            'filters' => $validated,
            'departments' => Department::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(Request $request): Response
    {
        $phone = (string) $request->query('phone', '');
        $departmentId = $request->query('department_id');
        $practitionerId = $request->query('practitioner_id');
        $date = $request->query('date');

        $slots = [];
        if (is_numeric($departmentId)) {
            $cutoffMinutes = (int) (Setting::get(
                $request->user()->hospital_id, Setting::BOOKING_CUTOFF_MINUTES
            ) ?? 120);

            $slots = AppointmentSlot::where('department_id', (int) $departmentId)
                ->where('is_active', true)
                ->whereRaw('booked_count < capacity')
                ->where('starts_at', '>', Carbon::now()->addMinutes($cutoffMinutes))
                ->when(is_numeric($practitionerId), fn ($query) => $query->where('practitioner_id', (int) $practitionerId))
                ->when(is_string($date) && $date !== '', fn ($query) => $query->whereDate('date', $date))
                ->with('practitioner:id,full_name')
                ->orderBy('starts_at')
                ->limit(200)
                ->get();
        }

        return Inertia::render('Staff/Appointments/Create', [
            'phone' => $phone,
            'patient' => $phone !== '' ? Patient::where('phone', $phone)->first()?->only('id', 'full_name', 'phone') : null,
            'departments' => Department::where('is_active', true)->orderBy('name')->get(['id', 'name', 'payment_mode', 'base_fee_kobo']),
            'department_id' => is_numeric($departmentId) ? (int) $departmentId : null,
            'practitioner_id' => is_numeric($practitionerId) ? (int) $practitionerId : null,
            'date' => is_string($date) ? $date : null,
            'slots' => $slots,
        ]);
    }

    public function store(Request $request, BookingService $bookings): RedirectResponse
    {
        $validated = $request->validate([
            'patient_id' => ['required', 'integer', Rule::exists('patients', 'id')],
            'department_id' => ['required', 'integer', Rule::exists('departments', 'id')],
            'practitioner_id' => ['nullable', 'integer', Rule::exists('practitioners', 'id')],
            'slot_id' => ['required', 'integer', Rule::exists('appointment_slots', 'id')],
            'payment_mode' => ['required', Rule::in([Appointment::PAY_MODE_ONLINE, Appointment::PAY_MODE_PHYSICAL])],
        ]);

        $patient = Patient::findOrFail($validated['patient_id']);
        $this->authorize('view', $patient);

        $appointment = $bookings->book(
            $patient,
            Department::findOrFail($validated['department_id']),
            isset($validated['practitioner_id']) ? Practitioner::findOrFail($validated['practitioner_id']) : null,
            AppointmentSlot::findOrFail($validated['slot_id']),
            $validated['payment_mode']
        );

        return redirect('/staff/appointments')->with('status', "Appointment booked for {$patient->full_name}.");
    }

    public function show(Appointment $appointment): Response
    {
        $this->authorize('view', $appointment);

        return Inertia::render('Staff/Appointments/Show', [
            'appointment' => $appointment->load(['patient', 'department:id,name', 'practitioner:id,full_name', 'slot:id,starts_at,ends_at', 'payment']),
        ]);
    }

    public function reschedule(Request $request, Appointment $appointment, BookingService $bookings): RedirectResponse
    {
        $this->authorize('update', $appointment);

        $validated = $request->validate([
            'slot_id' => ['required', 'integer', Rule::exists('appointment_slots', 'id')],
        ]);

        $bookings->reschedule($appointment, AppointmentSlot::findOrFail($validated['slot_id']));

        return back()->with('status', 'Appointment moved. Payment carries over.');
    }

    public function cancel(Request $request, Appointment $appointment, BookingService $bookings): RedirectResponse
    {
        $this->authorize('update', $appointment);

        $validated = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);

        $bookings->cancel($appointment, $validated['reason'] ?? null, $request->user());

        return back()->with('status', 'Appointment cancelled.');
    }

    public function noShow(Request $request, Appointment $appointment, BookingService $bookings): RedirectResponse
    {
        $this->authorize('update', $appointment);

        $bookings->markNoShow($appointment, $request->user());

        return back()->with('status', 'Marked as no-show.');
    }
}
