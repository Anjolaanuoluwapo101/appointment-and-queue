<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Practitioner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Minimal practitioner management (PRD §22). Unblocks schedule setup;
 * login linking and full staff-account management follow separately.
 */
class PractitionerController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Practitioners/Index', [
            'practitioners' => Practitioner::with('departments:id,name')->orderBy('full_name')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Practitioners/Create', [
            'departments' => Department::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        $departmentIds = $validated['department_ids'];
        unset($validated['department_ids']);

        $practitioner = Practitioner::create($validated + ['hospital_id' => $request->user()->hospital_id]);
        $practitioner->departments()->sync($departmentIds);

        AuditLog::record($practitioner->hospital_id, $request->user()->id, 'practitioner_created', $practitioner);

        return redirect('/admin/practitioners')->with('status', 'Practitioner created.');
    }

    public function edit(Practitioner $practitioner): Response
    {
        return Inertia::render('Admin/Practitioners/Edit', [
            'practitioner' => $practitioner->load('departments:id'),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Practitioner $practitioner): RedirectResponse
    {
        $validated = $this->validated($request);
        $departmentIds = $validated['department_ids'];
        unset($validated['department_ids']);

        $practitioner->update($validated);
        $practitioner->departments()->sync($departmentIds);

        AuditLog::record($practitioner->hospital_id, $request->user()->id, 'practitioner_updated', $practitioner);

        return redirect('/admin/practitioners')->with('status', 'Practitioner updated.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'specialisation' => ['required', 'string', 'max:255'],
            'qualifications' => ['nullable', 'string'],
            'bio' => ['nullable', 'string'],
            'internal_contact' => ['nullable', 'string', 'max:255'],
            'availability' => ['required', Rule::in([
                Practitioner::AVAILABILITY_ACTIVE,
                Practitioner::AVAILABILITY_ON_LEAVE,
                Practitioner::AVAILABILITY_UNAVAILABLE,
            ])],
            'department_ids' => ['required', 'array', 'min:1'],
            'department_ids.*' => ['integer', Rule::exists('departments', 'id')],
        ]);
    }
}
