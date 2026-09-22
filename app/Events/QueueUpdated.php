<?php

namespace App\Events;

use App\Models\Department;
use App\Services\QueueService;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Department queue changed: staff boards and waiting-room displays
 * refresh from the snapshot (PRD §9, §11).
 */
class QueueUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public int $departmentId) {}

    public function broadcastOn(): Channel
    {
        return new Channel("queue.{$this->departmentId}");
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        $department = Department::find($this->departmentId);

        return $department !== null
            ? app(QueueService::class)->snapshot($department)
            : ['department' => ['id' => $this->departmentId]];
    }
}
