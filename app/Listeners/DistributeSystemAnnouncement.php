<?php

namespace App\Listeners;

use App\Events\SystemAnnouncementPublishedEvent;
use App\Services\AuditLogger;
use App\Services\StudentNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Throwable;

class DistributeSystemAnnouncement implements ShouldQueue
{
    public function handle(SystemAnnouncementPublishedEvent $event): void
    {
        app(StudentNotificationService::class)->announcement($event->announcement);
    }

    public function failed(SystemAnnouncementPublishedEvent $event, Throwable $exception): void
    {
        app(AuditLogger::class)->write('notification.job_failed', $event->announcement, ['event' => SystemAnnouncementPublishedEvent::class, 'error' => $exception->getMessage()]);
    }
}
