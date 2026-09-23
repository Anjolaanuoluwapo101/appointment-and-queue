<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Resilient Free-Tier & Web-Trigger Schedule Rules (PRD §14)
|--------------------------------------------------------------------------
|
| Directives use windowed intervals (between) combined with DB idempotency.
| Even if web pings arrive at off-minute times (e.g. 06:07, 06:15), the
| very first ping in the window executes the task and DB checks prevent
| duplicate runs.
|
*/

// Generates upcoming slots on the first ping after midnight
Schedule::command('slots:generate')
    ->everyFifteenMinutes()
    ->between('00:00', '06:00')
    ->withoutOverlapping(60);

// Reconciles stale pending payments on every 15-minute ping
Schedule::command('payments:reconcile')
    ->everyFifteenMinutes()
    ->withoutOverlapping(60);

// Sends day-before and 2h day-of reminders
Schedule::command('reminders:send')
    ->everyFifteenMinutes()
    ->withoutOverlapping(60);

// Sends practitioner morning daily schedule summary on first morning ping after 06:00 AM
Schedule::command('summaries:send')
    ->everyFifteenMinutes()
    ->between('06:00', '12:00')
    ->withoutOverlapping(60);
