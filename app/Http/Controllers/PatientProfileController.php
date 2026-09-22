<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Patient manages their own profile (PRD §21, §26).
 */
class PatientProfileController extends Controller
{
    public function show(Request $request): Response
    {
        $patient = $request->user()->patient()->firstOrFail();

        return Inertia::render('Patient/Profile', ['patient' => $patient]);
    }

    public function update(Request $request): RedirectResponse
    {
        $patient = $request->user()->patient()->firstOrFail();

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:32'],
            'date_of_birth' => ['required', 'date'],
            'gender' => ['required', 'string', 'max:16'],
            'address' => ['required', 'string', 'max:255'],
            'secondary_contact_name' => ['nullable', 'string', 'max:255'],
            'secondary_contact_phone' => ['nullable', 'string', 'max:32'],
        ]);

        $patient->update($validated);
        $request->user()->update(['name' => $validated['full_name']]);

        return back()->with('status', 'Profile updated.');
    }
}
