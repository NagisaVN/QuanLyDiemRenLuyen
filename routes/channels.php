<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\HoatDong;

Broadcast::channel('users.{id}', function ($user, int $id): bool {
    return (int) $user->id === $id;
});

Broadcast::channel('activities.{activityId}', function ($user, int $activityId): bool {
    $student = $user->sinhVien;
    if (! $student || $student->trang_thai !== 'dang_hoc') {
        return false;
    }

    $activity = HoatDong::query()->with('khoas:id')->find($activityId);

    return $activity && ($activity->khoas->isEmpty() || $activity->khoas->contains('id', $student->lop?->khoa_id));
});
