<?php

namespace App\Events;

use App\Models\StudentNotification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StudentNotificationCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly StudentNotification $notification) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("users.{$this->notification->user_id}");
    }

    public function broadcastAs(): string
    {
        return 'student.notification.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->notification->id,
            'title' => $this->notification->title,
            'message' => $this->notification->content,
            'type' => $this->notification->type,
            'url' => $this->notification->action_url,
            'created_at' => $this->notification->created_at?->toIso8601String(),
        ];
    }
}
