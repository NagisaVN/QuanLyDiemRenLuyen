<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('users.{id}', function ($user, int $id): bool {
    return (int) $user->id === $id;
});
