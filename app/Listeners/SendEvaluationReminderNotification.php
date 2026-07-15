<?php

namespace App\Listeners;

use App\Events\EvaluationClosingSoonEvent;
use App\Services\AuditLogger;
use App\Services\StudentNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Throwable;

class SendEvaluationReminderNotification implements ShouldQueue
{
    public function handle(EvaluationClosingSoonEvent $event): void
    {
        app(StudentNotificationService::class)->evaluationReminder($event->period, $event->milestone);
    }

    public function failed(EvaluationClosingSoonEvent $event, Throwable $exception): void
    {
        app(AuditLogger::class)->write('notification.job_failed', $event->period, ['event' => EvaluationClosingSoonEvent::class, 'milestone' => $event->milestone, 'error' => $exception->getMessage()]);
    }
}
