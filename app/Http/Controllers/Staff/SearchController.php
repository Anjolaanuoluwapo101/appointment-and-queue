<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\Patient;
use App\Models\Practitioner;
use App\Models\QueueEntry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Cross-entity search and filter for staff (PRD §16): patients by name,
 * phone, patient_number, or ID; appointments by ID; queue entries by number; plus
 * department / practitioner / status / date filters.
 */
class SearchController extends Controller
{
    public function search(Request $request): Response
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'department_id' => ['nullable', 'integer'],
            'practitioner_id' => ['nullable', 'integer'],
            'appointment_status' => ['nullable', 'string'],
            'queue_status' => ['nullable', 'string'],
            'date' => ['nullable', 'date'],
        ]);

        $q = trim($validated['q'] ?? '');
        $hospitalId = $request->user()->hospital_id;

        $patients = [];
        $appointments = [];
        $queueEntries = [];

        if ($q !== '') {
            $like = '%'.mb_strtolower($q).'%';

            $patients = Patient::forHospital($hospitalId)
                ->where(fn ($query) => $query
                    ->whereRaw('LOWER(full_name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(patient_number) LIKE ?', [$like])
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->when(is_numeric($q), fn ($query) => $query->orWhere('id', (int) $q)))
                ->orderBy('full_name')
                ->limit(25)
                ->get(['id', 'patient_number', 'full_name', 'phone', 'email']);

            $appointments = is_numeric($q)
                ? Appointment::forHospital($hospitalId)
                    ->where('id', (int) $q)
                    ->with(['patient:id,patient_number,full_name', 'department:id,name'])
                    ->limit(25)
                    ->get()
                : [];

            $queueEntries = QueueEntry::forHospital($hospitalId)
                ->whereRaw('LOWER(queue_number) LIKE ?', [$like])
                ->when($validated['date'] ?? null, fn ($query, $date) => $query->whereDate('queue_date', $date))
                ->with(['patient:id,patient_number,full_name', 'department:id,name'])
                ->orderByDesc('id')
                ->limit(25)
                ->get();
        }

        if (($validated['department_id'] ?? null) || ($validated['practitioner_id'] ?? null)
            || ($validated['appointment_status'] ?? null) || ($validated['date'] ?? null)
        ) {
            $appointments = Appointment::forHospital($hospitalId)
                ->when($validated['department_id'] ?? null, fn ($query, $id) => $query->where('department_id', $id))
                ->when($validated['practitioner_id'] ?? null, fn ($query, $id) => $query->where('practitioner_id', $id))
                ->when($validated['appointment_status'] ?? null, fn ($query, $status) => $query->where('status', $status))
                ->when($validated['date'] ?? null, fn ($query, $date) => $query->whereDate('scheduled_at', $date))
                ->with(['patient:id,patient_number,full_name', 'department:id,name', 'practitioner:id,full_name'])
                ->orderBy('scheduled_at')
                ->limit(100)
                ->get();

            if (($validated['queue_status'] ?? null) && ($validated['date'] ?? null)) {
                $queueEntries = QueueEntry::forHospital($hospitalId)
                    ->when($validated['department_id'] ?? null, fn ($query, $id) => $query->where('department_id', $id))
                    ->when($validated['practitioner_id'] ?? null, fn ($query, $id) => $query->where('practitioner_id', $id))
                    ->where('status', $validated['queue_status'])
                    ->whereDate('queue_date', $validated['date'])
                    ->with(['patient:id,patient_number,full_name', 'department:id,name'])
                    ->orderBy('id')
                    ->limit(100)
                    ->get();
            }
        }

        return Inertia::render('Staff/Search', [
            'filters' => $validated,
            'patients' => $patients,
            'appointments' => $appointments,
            'queue_entries' => $queueEntries,
            'departments' => Department::forHospital($hospitalId)->orderBy('name')->get(['id', 'name']),
            'practitioners' => Practitioner::forHospital($hospitalId)->orderBy('full_name')->get(['id', 'full_name']),
        ]);
    }
}
