<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\QueueEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Receptionist registers patients on behalf of arrivals (PRD §5).
 * No login account is created here — walk-in quick-add (Phase 3) covers
 * minimal name+phone capture at the desk.
 */
class PatientController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $search = trim((string) $request->input('search', ''));
        $gender = trim((string) $request->input('gender', ''));
        $sort = trim((string) $request->input('sort', 'full_name'));

        $query = Patient::where('hospital_id', $user->hospital_id);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('patient_number', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($gender !== '' && $gender !== 'all') {
            $query->where('gender', $gender);
        }

        if (in_array($sort, ['full_name', 'created_at', 'patient_number'], true)) {
            $direction = $sort === 'created_at' ? 'desc' : 'asc';
            $query->orderBy($sort, $direction);
        } else {
            $query->orderBy('full_name', 'asc');
        }

        $patients = $query->paginate(15)->withQueryString();

        return Inertia::render('Staff/Patients/Index', [
            'patients' => $patients,
            'filters' => [
                'search' => $search,
                'gender' => $gender ?: 'all',
                'sort' => $sort,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Staff/Patients/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $hospitalId = $request->user()->hospital_id;

        $validated = $request->validate([
            'patient_number' => [
                'nullable', 'string', 'max:50',
                Rule::unique('patients')->where('hospital_id', $hospitalId),
            ],
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => [
                'required', 'string', 'max:32',
                Rule::unique('patients')->where('hospital_id', $hospitalId),
            ],
            'email' => ['required', 'email', 'max:255'],
            'date_of_birth' => ['required', 'date'],
            'gender' => ['required', 'string', 'max:16'],
            'address' => ['required', 'string', 'max:255'],
            'secondary_contact_name' => ['nullable', 'string', 'max:255'],
            'secondary_contact_phone' => ['nullable', 'string', 'max:32'],
        ]);

        Patient::create($validated + ['hospital_id' => $hospitalId]);

        return redirect('/staff/patients')->with('status', 'Patient registered.');
    }

    public function show(Request $request, Patient $patient): Response
    {
        abort_if($patient->hospital_id !== $request->user()->hospital_id, 403);

        $appointments = Appointment::where('patient_id', $patient->id)
            ->with(['department:id,name', 'practitioner:id,full_name', 'payment'])
            ->orderByDesc('scheduled_at')
            ->get();

        $queueEntries = QueueEntry::where('patient_id', $patient->id)
            ->with(['department:id,name', 'practitioner:id,full_name'])
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Staff/Patients/Show', [
            'patient' => $patient,
            'appointments' => $appointments,
            'queue_entries' => $queueEntries,
        ]);
    }
}
