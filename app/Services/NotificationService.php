<?php

namespace App\Services;

use App\Jobs\SendNotificationMail;
use App\Models\Notification;
use App\Models\User;

/**
 * Event-driven notices (PRD §14): inbox row first, email queued behind
 * it. MVP channel is email; realtime queue ticks stay on WebSockets.
 */
class NotificationService
{
    /**
     * @return array<string, mixed> payload for logs
     */
    public function send(
        ?User $user,
        int $hospitalId,
        string $type,
        string $title,
        string $body,
        ?string $link = null,
        bool $mail = true,
    ): ?Notification {
        if ($user === null) {
            return null;
        }

        $notification = Notification::create([
            'hospital_id' => $hospitalId,
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'link' => $link,
        ]);

        if ($mail && $user->email !== null && $user->email !== '') {
            SendNotificationMail::dispatch($notification->id);
        }

        return $notification;
    }

    /**
     * Idempotent send: skips when the same user+type+link notice already
     * exists today. Used by scheduled reminders.
     */
    public function sendUnique(
        ?User $user,
        int $hospitalId,
        string $type,
        string $title,
        string $body,
        ?string $link = null,
    ): ?Notification {
        if ($user === null) {
            return null;
        }

        $exists = Notification::where('user_id', $user->id)
            ->where('type', $type)
            ->where('link', $link)
            ->whereDate('created_at', today()->toDateString())
            ->exists();

        if ($exists) {
            return null;
        }

        return $this->send($user, $hospitalId, $type, $title, $body, $link);
    }

    /**
     * Fans out to active staff users of a hospital filtered by role.
     *
     * @param  array<int, string>  $roles
     */
    public function sendToStaff(
        int $hospitalId,
        array $roles,
        string $type,
        string $title,
        string $body,
        ?string $link = null,
    ): void {
        User::where('hospital_id', $hospitalId)
            ->where('is_active', true)
            ->whereIn('role', $roles)
            ->each(function (User $user) use ($hospitalId, $type, $title, $body, $link): void {
                $this->send($user, $hospitalId, $type, $title, $body, $link);
            });
    }
}
