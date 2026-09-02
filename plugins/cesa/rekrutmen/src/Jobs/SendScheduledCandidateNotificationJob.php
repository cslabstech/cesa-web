<?php

namespace Cesa\Rekrutmen\Jobs;

use Cesa\Rekrutmen\Models\ScheduledNotification;
use Cesa\Rekrutmen\Services\ScheduledNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendScheduledCandidateNotificationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public int $scheduledNotificationId
    ) {
        $queue = config('rekrutmen.notifications.queue') ?? 'default';
        $this->onQueue($queue);
    }

    public function handle(): void
    {
        $notification = ScheduledNotification::find($this->scheduledNotificationId);

        if (! $notification) {
            Log::warning("Scheduled notification #{$this->scheduledNotificationId} not found.");

            return;
        }

        if ($notification->status !== ScheduledNotification::STATUS_PENDING) {
            return;
        }

        // Only execute if time is reached
        if ($notification->scheduled_at->isFuture()) {
            if (config('queue.default') !== 'sync') {
                $delayInSeconds = max(0, now()->diffInSeconds($notification->scheduled_at, false));
                self::dispatch($notification->id)->delay($delayInSeconds);
            }

            return;
        }

        try {
            app(ScheduledNotificationService::class)->executeScheduled($notification);
        } catch (Throwable $e) {
            Log::error("Queue job failed for scheduled notification #{$notification->id}: ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'rekrutmen',
            'scheduled-notification',
            'notification:'.$this->scheduledNotificationId,
        ];
    }
}
