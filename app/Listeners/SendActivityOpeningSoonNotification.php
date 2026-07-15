<?php

namespace App\Listeners;

use App\Events\ActivityOpeningSoonEvent;
use App\Services\AuditLogger;
use App\Services\StudentNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Throwable;

class SendActivityOpeningSoonNotification implements ShouldQueue
{
    public function handle(ActivityOpeningSoonEvent $event): void
    {
        app(StudentNotificationService::class)->activityOpeningSoon($event->activity);
    }

    public function failed(ActivityOpeningSoonEvent $event, Throwable $exception): void
    {
        app(AuditLogger::class)->write('notification.job_failed', $event->activity, [
            'event' => ActivityOpeningSoonEvent::class,
            'error' => $exception->getMessage(),
        ]);
    }
}
