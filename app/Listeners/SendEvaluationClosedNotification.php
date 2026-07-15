<?php

namespace App\Listeners;

use App\Events\EvaluationClosedEvent;
use App\Services\AuditLogger;
use App\Services\StudentNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Throwable;

class SendEvaluationClosedNotification implements ShouldQueue
{
    public function handle(EvaluationClosedEvent $event): void
    {
        app(StudentNotificationService::class)->evaluationClosed($event->period);
    }

    public function failed(EvaluationClosedEvent $event, Throwable $exception): void
    {
        app(AuditLogger::class)->write('notification.job_failed', $event->period, ['event' => EvaluationClosedEvent::class, 'error' => $exception->getMessage()]);
    }
}
