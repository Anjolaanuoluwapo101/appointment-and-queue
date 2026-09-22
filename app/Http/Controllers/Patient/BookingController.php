<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\Department;
use App\Models\Practitioner;
use App\Models\QueueEntry;
use App\Models\Setting;
use App\Services\BookingService;
use App\Services\FeeResolver;
use App\Services\PaymentService;
use App\Services\QueueService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Patient self-service booking (PRD §6, §7): browse → slot → confirm →
 * pay online now or pay at hospital. Payment enforced at queue entry.
 */
class BookingController extends Controller
{
    public function departments(): Response
    {
        return Inertia::render('Patient/Book/Departments', [
            'departments' => Department::where('is_active', true)->orderBy('name')
                ->get(['id', 'name', 'payment_mode', 'base_fee_kobo']),
        ]);
    }

    public function practitioners(Request $request, Department $department, FeeResolver $fees): Response
    {
        $practitioners = $department->practitioners()
            ->where('availability', Practitioner::AVAILABILITY_ACTIVE)
            ->orderBy('full_name')
            ->get(['practitioners.id', 'full_name', 'specialisation', 'bio'])
            ->map(fn ($practitioner) => [
                ...$practitioner->toArray(),
                'fee_kobo' => $fees->resolve($department->hospital_id, $department->id, $practitioner->id),
            ]);

        return Inertia::render('Patient/Book/Practitioners', [
            'department' => $department->only('id', 'name', 'payment_mode', 'base_fee_kobo'),
            'practitioners' => $practitioners,
        ]);
    }

    public function slots(Request $request, Department $department, FeeResolver $fees): Response
    {
        $validated = $request->validate([
            'practitioner_id' => ['nullable', 'integer', Rule::exists('practitioners', 'id')],
            'date' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        $cutoffMinutes = (int) (Setting::get(
            $department->hospital_id, Setting::BOOKING_CUTOFF_MINUTES
        ) ?? 120);

        $slots = AppointmentSlot::where('department_id', $department->id)
            ->where('is_active', true)
            ->whereRaw('booked_count < capacity')
            ->where('starts_at', '>', Carbon::now()->addMinutes($cutoffMinutes))
            ->when($validated['practitioner_id'] ?? null, fn ($query, $id) => $query->where('practitioner_id', $id))
            ->when($validated['date'] ?? null, fn ($query, $date) => $query->whereDate('date', $date))
            ->with('practitioner:id,full_name')
            ->orderBy('starts_at')
            ->limit(200)
            ->get();

        return Inertia::render('Patient/Book/Slots', [
            'department' => $department->only('id', 'name', 'payment_mode', 'base_fee_kobo'),
            'practitioner_id' => $validated['practitioner_id'] ?? null,
            'date' => $validated['date'] ?? null,
            'fee_kobo' => $fees->resolve(
                $department->hospital_id, $department->id,
                isset($validated['practitioner_id']) ? (int) $validated['practitioner_id'] : null
            ),
            'slots' => $slots,
        ]);
    }

    public function store(Request $request, BookingService $bookings, FeeResolver $fees): RedirectResponse
    {
        $validated = $request->validate([
            'department_id' => ['required', 'integer', Rule::exists('departments', 'id')],
            'practitioner_id' => ['nullable', 'integer', Rule::exists('practitioners', 'id')],
            'slot_id' => ['required', 'integer', Rule::exists('appointment_slots', 'id')],
            'payment_mode' => ['required', Rule::in([Appointment::PAY_MODE_ONLINE, Appointment::PAY_MODE_PHYSICAL])],
        ]);

        $patient = $request->user()->patient()->firstOrFail();
        $department = Department::findOrFail($validated['department_id']);
        $practitioner = isset($validated['practitioner_id'])
            ? Practitioner::findOrFail($validated['practitioner_id'])
            : null;
        $slot = AppointmentSlot::findOrFail($validated['slot_id']);

        $appointment = $bookings->book($patient, $department, $practitioner, $slot, $validated['payment_mode']);

        return redirect("/patient/appointments/{$appointment->id}")
            ->with('status', 'Appointment booked. Fee: ₦'.number_format($fees->resolve(
                $department->hospital_id, $department->id, $appointment->practitioner_id
            ) / 100, 2));
    }

    public function index(Request $request): Response
    {
        $patient = $request->user()->patient()->firstOrFail();

        return Inertia::render('Patient/Appointments/Index', [
            'upcoming' => Appointment::where('patient_id', $patient->id)
                ->whereIn('status', [Appointment::STATUS_SCHEDULED, Appointment::STATUS_PENDING_CLEARANCE])
                ->with(['department:id,name', 'practitioner:id,full_name'])
                ->orderBy('scheduled_at')
                ->get(),
            'history' => Appointment::where('patient_id', $patient->id)
                ->whereNotIn('status', [Appointment::STATUS_SCHEDULED, Appointment::STATUS_PENDING_CLEARANCE])
                ->with(['department:id,name', 'practitioner:id,full_name'])
                ->orderByDesc('scheduled_at')
                ->limit(50)
                ->get(),
        ]);
    }

    public function show(Appointment $appointment, FeeResolver $fees, QueueService $queue): Response
    {
        $this->authorize('view', $appointment);

        $entry = QueueEntry::where('appointment_id', $appointment->id)
            ->whereDate('queue_date', Carbon::now()->toDateString())
            ->first();

        return Inertia::render('Patient/Appointments/Show', [
            'appointment' => $appointment->load(['department:id,name,payment_mode', 'practitioner:id,full_name', 'slot:id,starts_at,ends_at']),
            'fee_kobo' => $fees->resolve($appointment->hospital_id, $appointment->department_id, $appointment->practitioner_id),
            'callback_url' => route('patient.appointments.pay.verify', $appointment),
            'queue' => $entry !== null ? $queue->positionFor($entry) + ['patient_id' => $entry->patient_id] : null,
        ]);
    }

    public function payInitialize(Appointment $appointment, PaymentService $payments, FeeResolver $fees): RedirectResponse
    {
        $this->authorize('update', $appointment);

        if ($appointment->payment_mode !== Appointment::PAY_MODE_ONLINE || $appointment->isPaid()) {
            return back()->withErrors(['payment' => 'Online payment is not available for this appointment.']);
        }

        $amount = $fees->resolve($appointment->hospital_id, $appointment->department_id, $appointment->practitioner_id);

        $result = $payments->initialize(
            $appointment,
            $appointment->patient->email ?? $appointment->patient->phone.'@placeholder.local',
            route('patient.appointments.pay.verify', $appointment),
            $amount
        );

        return Inertia::location($result['authorization_url']);
    }

    public function payVerify(Request $request, Appointment $appointment, PaymentService $payments): RedirectResponse
    {
        $this->authorize('update', $appointment);

        $payment = $appointment->payment;

        if ($payment === null) {
            return redirect("/patient/appointments/{$appointment->id}")
                ->withErrors(['payment' => 'No payment attempt found.']);
        }

        $ok = $payments->verifyByReference($payment->fresh());

        return redirect("/patient/appointments/{$appointment->id}")->with(
            'status', $ok ? 'Payment successful. Receipt available.' : 'Payment not confirmed yet. You can retry.'
        );
    }

    public function cancel(Request $request, Appointment $appointment, BookingService $bookings): RedirectResponse
    {
        $this->authorize('update', $appointment);

        $validated = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);

        $bookings->cancel($appointment, $validated['reason'] ?? null, $request->user());

        return redirect('/patient/appointments')->with('status', 'Appointment cancelled. Refund processed if you paid online.');
    }

    public function rescheduleForm(Appointment $appointment): Response
    {
        $this->authorize('update', $appointment);

        abort_unless($appointment->status === Appointment::STATUS_SCHEDULED, 422, 'Only scheduled appointments can be rescheduled.');

        $cutoffMinutes = (int) (Setting::get(
            $appointment->hospital_id, Setting::BOOKING_CUTOFF_MINUTES
        ) ?? 120);

        return Inertia::render('Patient/Appointments/Reschedule', [
            'appointment' => $appointment->load('department:id,name'),
            'slots' => AppointmentSlot::where('department_id', $appointment->department_id)
                ->where('is_active', true)
                ->whereRaw('booked_count < capacity')
                ->where('starts_at', '>', Carbon::now()->addMinutes($cutoffMinutes))
                ->with('practitioner:id,full_name')
                ->orderBy('starts_at')
                ->limit(200)
                ->get(),
        ]);
    }

    public function reschedule(Request $request, Appointment $appointment, BookingService $bookings): RedirectResponse
    {
        $this->authorize('update', $appointment);

        $validated = $request->validate([
            'slot_id' => ['required', 'integer', Rule::exists('appointment_slots', 'id')],
        ]);

        $bookings->reschedule($appointment, AppointmentSlot::findOrFail($validated['slot_id']));

        return redirect("/patient/appointments/{$appointment->id}")->with('status', 'Appointment rescheduled. Your payment carries over.');
    }
}
