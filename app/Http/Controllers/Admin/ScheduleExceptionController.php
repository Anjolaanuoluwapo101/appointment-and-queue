<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppointmentSlot;
use App\Models\AuditLog;
use App\Models\Practitioner;
use App\Models\ScheduleException;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Temporary schedule overrides: days off and adjusted hours (PRD §8).
 * Unlike schedule edits, exceptions are the tool for sick days — affected
 * bookings are reported so staff can cancel/reschedule them.
 */
class ScheduleExceptionController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Exceptions/Index', [
            'exceptions' => ScheduleException::with(['practitioner:id,full_name', 'department:id,name'])
                ->whereDate('date', '>=', Carbon::today()->toDateString())
                ->orderBy('date')
                ->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Exceptions/Create', [
            'practitioners' => Practitioner::with('departments:id,name')->orderBy('full_name')->get(['id', 'full_name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $hospitalId = $request->user()->hospital_id;

        $validated = $request->validate([
            'practitioner_id' => ['required', 'integer', Rule::exists('practitioners', 'id')->where('hospital_id', $hospitalId)],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')->where('hospital_id', $hospitalId)],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'type' => ['required', Rule::in([ScheduleException::TYPE_DAY_OFF, ScheduleException::TYPE_ADJUSTED])],
            'start_time' => ['required_if:type,adjusted', 'nullable', 'date_format:H:i'],
            'end_time' => ['required_if:type,adjusted', 'nullable', 'date_format:H:i', 'after:start_time'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $exception = ScheduleException::create($validated + ['hospital_id' => $hospitalId]);

        AuditLog::record($hospitalId, $request->user()->id, 'schedule_exception_created', $exception);

        $affected = AppointmentSlot::where('practitioner_id', $exception->practitioner_id)
            ->when($exception->department_id !== null, fn ($query) => $query->where('department_id', $exception->department_id))
            ->whereDate('date', $exception->date->toDateString())
            ->where('booked_count', '>', 0)
            ->count();

        $message = 'Exception saved.';
        if ($affected > 0) {
            $message .= " {$affected} booked slot(s) on {$exception->date->toDateString()} need cancelling or rescheduling.";
        }

        return redirect('/admin/exceptions')->with('status', $message);
    }

    public function destroy(ScheduleException $exception): RedirectResponse
    {
        AuditLog::record($exception->hospital_id, request()->user()->id, 'schedule_exception_deleted', $exception);
        $exception->delete();

        return redirect('/admin/exceptions')->with('status', 'Exception removed.');
    }
}
