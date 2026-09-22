<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Patient self-registration (PRD §3, §21). Single-hospital MVP: the new
 * account attaches to the first active hospital.
 */
class RegisterController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:32'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'date_of_birth' => ['required', 'date'],
            'gender' => ['required', 'string', 'max:16'],
            'address' => ['required', 'string', 'max:255'],
            'secondary_contact_name' => ['nullable', 'string', 'max:255'],
            'secondary_contact_phone' => ['nullable', 'string', 'max:32'],
        ]);

        $hospital = Hospital::where('is_active', true)->orderBy('id')->firstOrFail();

        $user = User::create([
            'name' => $validated['full_name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'hospital_id' => $hospital->id,
            'role' => User::ROLE_PATIENT,
        ]);

        Patient::create([
            'hospital_id' => $hospital->id,
            'user_id' => $user->id,
            'full_name' => $validated['full_name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'date_of_birth' => $validated['date_of_birth'],
            'gender' => $validated['gender'],
            'address' => $validated['address'],
            'secondary_contact_name' => $validated['secondary_contact_name'] ?? null,
            'secondary_contact_phone' => $validated['secondary_contact_phone'] ?? null,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        app(NotificationService::class)->send(
            $user, $hospital->id, 'welcome',
            'Account created successfully',
            "Welcome, {$user->name}. You can now book appointments online.",
            '/patient/book'
        );

        AuditLog::record($hospital->id, $user->id, 'patient_registered', $user);

        return redirect('/patient/dashboard');
    }
}
