<?php

namespace App\Listeners;

use App\Events\ActivityOpenedEvent;
use App\Services\AuditLogger;
use App\Services\StudentNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Throwable;

class SendActivityOpenedNotification implements ShouldQueue
{
    public function handle(ActivityOpenedEvent $event): void
    {
        app(StudentNotificationService::class)->activityOpened($event->activity);
    }

    public function failed(ActivityOpenedEvent $event, Throwable $exception): void
    {
        app(AuditLogger::class)->write('notification.job_failed', $event->activity, ['event' => ActivityOpenedEvent::class, 'error' => $exception->getMessage()]);
    }
}
