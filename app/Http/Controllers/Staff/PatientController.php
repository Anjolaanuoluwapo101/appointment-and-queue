<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Patient;
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
    public function index(): Response
    {
        $patients = Patient::orderBy('full_name')->limit(100)->get([
            'id', 'full_name', 'phone', 'email', 'gender', 'date_of_birth',
        ]);

        return Inertia::render('Staff/Patients/Index', ['patients' => $patients]);
    }

    public function create(): Response
    {
        return Inertia::render('Staff/Patients/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $hospitalId = $request->user()->hospital_id;

        $validated = $request->validate([
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
}
