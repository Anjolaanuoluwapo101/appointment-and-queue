<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Services\QueueService;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public waiting-room display board (PRD §11): no login, one screen per
 * department, live numbers over the public queue channel.
 */
class DisplayController extends Controller
{
    public function show(Department $department, QueueService $queue): Response
    {
        abort_unless($department->is_active, 404);

        return Inertia::render('Display/Board', [
            'snapshot' => $queue->snapshot($department, null, true),
        ]);
    }
}
