<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin manages departments (PRD §23): edit, deactivate, create.
 * Delete is blocked while practitioners, schedules, or appointments
 * still reference the department — deactivate instead.
 */
class DepartmentController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Departments/Index', [
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Departments/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        Department::create($validated + ['hospital_id' => $request->user()->hospital_id]);

        AuditLog::record($request->user()->hospital_id, $request->user()->id, 'department_created',
            Department::where('hospital_id', $request->user()->hospital_id)->where('name', $validated['name'])->first());

        return redirect('/admin/departments')->with('status', 'Department created.');
    }

    public function edit(Department $department): Response
    {
        return Inertia::render('Admin/Departments/Edit', ['department' => $department]);
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $before = $department->only(['name', 'queue_prefix', 'room_label', 'payment_mode', 'base_fee_kobo', 'is_active']);
        $department->update($this->validated($request, $department->id));

        AuditLog::record($department->hospital_id, $request->user()->id, 'department_updated', $department,
            $before, $department->fresh()->only(['name', 'queue_prefix', 'room_label', 'payment_mode', 'base_fee_kobo', 'is_active']));

        return redirect('/admin/departments')->with('status', 'Department updated.');
    }

    public function destroy(Request $request, Department $department): RedirectResponse
    {
        if ($department->practitioners()->exists()
            || (Schema::hasTable('appointments') && Appointment::where('department_id', $department->id)->exists())
        ) {
            return back()->withErrors(['department' => 'Department has linked practitioners or appointments. Deactivate it instead.']);
        }

        $department->delete();

        AuditLog::record($request->user()->hospital_id, $request->user()->id, 'department_deleted', $department);

        return redirect('/admin/departments')->with('status', 'Department deleted.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('departments')->where('hospital_id', $request->user()->hospital_id)->ignore($ignoreId),
            ],
            'queue_prefix' => ['required', 'string', 'max:3', 'regex:/^[A-Z]{1,3}$/'],
            'room_label' => ['nullable', 'string', 'max:255'],
            'payment_mode' => ['required', Rule::in([
                Department::PAYMENT_ALLOW_BOTH,
                Department::PAYMENT_PHYSICAL_ONLY,
                Department::PAYMENT_ONLINE_REQUIRED,
            ])],
            'base_fee_kobo' => ['required', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }
}
