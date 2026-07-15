<?php

namespace App\Listeners;

use App\Events\EvaluationOpenedEvent;
use App\Services\AuditLogger;
use App\Services\StudentNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Throwable;

class SendEvaluationOpenedNotification implements ShouldQueue
{
    public function handle(EvaluationOpenedEvent $event): void
    {
        app(StudentNotificationService::class)->evaluationOpened($event->period);
    }

    public function failed(EvaluationOpenedEvent $event, Throwable $exception): void
    {
        app(AuditLogger::class)->write('notification.job_failed', $event->period, ['event' => EvaluationOpenedEvent::class, 'error' => $exception->getMessage()]);
    }
}
