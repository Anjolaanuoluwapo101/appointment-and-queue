<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Practitioner;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class StaffController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Staff/Index', [
            'staff' => User::where('hospital_id', $request->user()->hospital_id)
                ->whereIn('role', [User::ROLE_RECEPTIONIST, User::ROLE_PRACTITIONER, User::ROLE_ADMIN])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Admin/Staff/Create', [
            'practitioners' => Practitioner::where('hospital_id', $request->user()->hospital_id)
                ->whereNull('user_id')
                ->orderBy('full_name')
                ->get(['id', 'full_name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::defaults()],
            'role' => ['required', Rule::in([User::ROLE_RECEPTIONIST, User::ROLE_PRACTITIONER, User::ROLE_ADMIN])],
            'practitioner_id' => [
                'nullable',
                'integer',
                Rule::exists('practitioners', 'id')->where('hospital_id', $request->user()->hospital_id),
            ],
        ]);

        $practitionerId = $validated['practitioner_id'] ?? null;
        unset($validated['practitioner_id']);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'hospital_id' => $request->user()->hospital_id,
            'is_active' => true,
        ]);

        Staff::create([
            'hospital_id' => $user->hospital_id,
            'user_id' => $user->id,
        ]);

        if ($user->role === User::ROLE_PRACTITIONER && $practitionerId) {
            Practitioner::where('id', $practitionerId)->update(['user_id' => $user->id]);
        }

        AuditLog::record($user->hospital_id, $request->user()->id, 'staff_created', $user);

        return redirect('/admin/staff')->with('status', 'Staff account created.');
    }

    public function edit(Request $request, User $staff): Response
    {
        abort_if($staff->hospital_id !== $request->user()->hospital_id, 403);
        abort_if(! $staff->isStaff(), 403);

        return Inertia::render('Admin/Staff/Edit', [
            'staff' => $staff,
        ]);
    }

    public function update(Request $request, User $staff): RedirectResponse
    {
        abort_if($staff->hospital_id !== $request->user()->hospital_id, 403);
        abort_if(! $staff->isStaff(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($staff->id)],
            'role' => ['required', Rule::in([User::ROLE_RECEPTIONIST, User::ROLE_PRACTITIONER, User::ROLE_ADMIN])],
        ]);

        $staff->update($validated);

        AuditLog::record($staff->hospital_id, $request->user()->id, 'staff_updated', $staff);

        return redirect('/admin/staff')->with('status', 'Staff account updated.');
    }

    public function toggleActive(Request $request, User $staff): RedirectResponse
    {
        abort_if($staff->hospital_id !== $request->user()->hospital_id, 403);
        abort_if(! $staff->isStaff(), 403);

        $newStatus = ! $staff->is_active;
        $staff->update(['is_active' => $newStatus]);

        if ($staff->role === User::ROLE_PRACTITIONER) {
            Practitioner::where('user_id', $staff->id)->update(['is_active' => $newStatus]);
        }

        AuditLog::record($staff->hospital_id, $request->user()->id, 'staff_status_toggled', $staff);

        return back()->with('status', 'Staff account status updated.');
    }
}
