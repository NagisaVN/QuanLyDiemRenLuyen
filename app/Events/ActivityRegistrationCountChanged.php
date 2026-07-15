<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ActivityRegistrationCountChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $activityId,
        public readonly int $registeredCount,
        public readonly ?int $remainingSlots,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("activities.{$this->activityId}");
    }

    public function broadcastAs(): string
    {
        return 'activity.registration-count.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'activity_id' => $this->activityId,
            'registered_count' => $this->registeredCount,
            'remaining_slots' => $this->remainingSlots,
        ];
    }
}
