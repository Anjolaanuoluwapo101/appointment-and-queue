<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * System-wide settings per hospital (PRD §8 booking rules, §14 queue
 * threshold, §24 sessions).
 */
class SettingsController extends Controller
{
    public function show(Request $request): Response
    {
        $hospitalId = $request->user()->hospital_id;

        return Inertia::render('Admin/Settings', [
            'settings' => [
                'session_lifetime_staff' => Setting::get($hospitalId, Setting::SESSION_LIFETIME_STAFF, '480'),
                'session_lifetime_patient' => Setting::get($hospitalId, Setting::SESSION_LIFETIME_PATIENT, '43200'),
                'queue_low_threshold' => Setting::get($hospitalId, Setting::QUEUE_LOW_THRESHOLD, '5'),
                'booking_window_days' => Setting::get($hospitalId, Setting::BOOKING_WINDOW_DAYS, '30'),
                'booking_cutoff_minutes' => Setting::get($hospitalId, Setting::BOOKING_CUTOFF_MINUTES, '120'),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'session_lifetime_staff' => ['required', 'integer', 'min:30', 'max:10080'],
            'session_lifetime_patient' => ['required', 'integer', 'min:30', 'max:43200'],
            'queue_low_threshold' => ['required', 'integer', 'min:1', 'max:100'],
            'booking_window_days' => ['required', 'integer', 'min:1', 'max:365'],
            'booking_cutoff_minutes' => ['required', 'integer', 'min:0', 'max:1440'],
        ]);

        $hospitalId = $request->user()->hospital_id;

        Setting::set($hospitalId, Setting::SESSION_LIFETIME_STAFF, (string) $validated['session_lifetime_staff']);
        Setting::set($hospitalId, Setting::SESSION_LIFETIME_PATIENT, (string) $validated['session_lifetime_patient']);
        Setting::set($hospitalId, Setting::QUEUE_LOW_THRESHOLD, (string) $validated['queue_low_threshold']);
        Setting::set($hospitalId, Setting::BOOKING_WINDOW_DAYS, (string) $validated['booking_window_days']);
        Setting::set($hospitalId, Setting::BOOKING_CUTOFF_MINUTES, (string) $validated['booking_cutoff_minutes']);

        AuditLog::record($hospitalId, $request->user()->id, 'settings_updated');

        return back()->with('status', 'Settings saved.');
    }
}
