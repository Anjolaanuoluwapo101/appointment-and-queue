<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Dual-portal session auth (PRD §24): /login/patient and /login/staff.
 * Staff portal accepts receptionist/practitioner/admin; role-based
 * routing happens after login.
 */
class LoginController extends Controller
{
    public function showPatient(): Response
    {
        return Inertia::render('Auth/Login', ['portal' => 'patient']);
    }

    public function showStaff(): Response
    {
        return Inertia::render('Auth/Login', ['portal' => 'staff']);
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request, string $portal): RedirectResponse
    {
        abort_unless(in_array($portal, ['patient', 'staff'], true), 404);

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        $allowed = $portal === 'patient'
            ? [User::ROLE_PATIENT]
            : [User::ROLE_RECEPTIONIST, User::ROLE_PRACTITIONER, User::ROLE_ADMIN];

        if ($user === null
            || ! in_array($user->role, $allowed, true)
            || ! $user->is_active
            || ! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended($this->homeFor($user));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function homeFor(User $user): string
    {
        if ($user->role === User::ROLE_PATIENT) {
            return '/patient/dashboard';
        }

        if ($user->role === User::ROLE_PRACTITIONER) {
            return '/staff/my-queue';
        }

        return '/staff/dashboard';
    }
}
