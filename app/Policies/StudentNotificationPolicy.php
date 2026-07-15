<?php

namespace App\Policies;

use App\Models\StudentNotification;
use App\Models\User;

class StudentNotificationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view student notifications');
    }

    public function view(User $user, StudentNotification $notification): bool
    {
        return $user->can('view student notifications') && $notification->user_id === $user->id;
    }

    public function update(User $user, StudentNotification $notification): bool
    {
        return $this->view($user, $notification);
    }
}
