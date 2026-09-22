<?php

namespace App\Events;

use App\Models\QueueEntry;
use App\Services\QueueService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * One patient's queue position changed: their dashboard updates live
 * with serving number, patients ahead, and called instruction (PRD §10).
 */
class PatientQueueUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public int $queueEntryId) {}

    public function broadcastOn(): PrivateChannel
    {
        $entry = QueueEntry::find($this->queueEntryId);

        return new PrivateChannel('patient.'.($entry?->patient_id ?? 0));
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        $entry = QueueEntry::with('department')->find($this->queueEntryId);

        return $entry !== null ? app(QueueService::class)->positionFor($entry) : [];
    }
}
