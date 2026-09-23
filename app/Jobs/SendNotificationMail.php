<?php

namespace App\Jobs;

use App\Mail\NoticeMail;
use App\Models\Notification;
use App\Models\NotificationLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

/**
 * Delivers one inbox notice over email with delivery tracking.
 * Retried 3x; final failure is recorded for admin follow-up.
 */
class SendNotificationMail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 120];

    public int $timeout = 60;

    public function __construct(public int $notificationId) {}

    public function handle(): void
    {
        $notification = Notification::with('user')->find($this->notificationId);

        if ($notification === null || $notification->user === null) {
            return;
        }

        try {
            Mail::to($notification->user->email)->send(new NoticeMail(
                $notification->title,
                $notification->body,
                $notification->link !== null ? url($notification->link) : null
            ));

            NotificationLog::create([
                'hospital_id' => $notification->hospital_id,
                'notification_id' => $notification->id,
                'channel' => 'mail',
                'recipient' => (string) $notification->user->email,
                'subject' => $notification->title,
                'status' => NotificationLog::STATUS_SENT,
            ]);
        } catch (\Throwable $exception) {
            NotificationLog::create([
                'hospital_id' => $notification->hospital_id,
                'notification_id' => $notification->id,
                'channel' => 'mail',
                'recipient' => (string) $notification->user->email,
                'subject' => $notification->title,
                'status' => NotificationLog::STATUS_FAILED,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function failed(?\Throwable $exception): void
    {
        $notification = Notification::find($this->notificationId);

        if ($notification === null) {
            return;
        }

        NotificationLog::create([
            'hospital_id' => $notification->hospital_id,
            'notification_id' => $notification->id,
            'channel' => 'mail',
            'recipient' => (string) $notification->user?->email,
            'subject' => $notification->title,
            'status' => NotificationLog::STATUS_FAILED,
            'error' => 'Exhausted retries: '.$exception?->getMessage(),
        ]);
    }
}
