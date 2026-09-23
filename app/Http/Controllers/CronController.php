<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

/**
 * Pure PHP Web Trigger for scheduled tasks (PRD §14).
 * Allows free-tier hosting platforms (Render, Fly.io, Vercel) or external
 * pingers (cron-job.org, UptimeRobot, GitHub Actions) to run the scheduler
 * securely over HTTP without requiring 24/7 background CLI daemons.
 */
class CronController extends Controller
{
    public function run(Request $request): JsonResponse
    {
        $expectedKey = (string) config('services.cron.key');
        $providedKey = (string) $request->query('key', $request->header('x-cron-key', ''));

        if ($expectedKey === '' || ! hash_equals($expectedKey, $providedKey)) {
            return response()->json(['error' => 'Unauthorized or unconfigured cron key.'], 401);
        }

        // Executes Artisan commands in-memory inside the PHP process (no shell/exec required)
        Artisan::call('schedule:run');

        return response()->json([
            'status' => 'success',
            'message' => 'Scheduler executed successfully in-memory.',
            'timestamp' => now()->toIso8601String(),
            'output' => trim(Artisan::output()),
        ]);
    }
}
