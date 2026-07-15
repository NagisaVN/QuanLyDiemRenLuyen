<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\StudentNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', StudentNotification::class);
        $filter = in_array($request->query('filter'), ['all', 'unread', 'evaluation', 'system'], true)
            ? $request->query('filter')
            : 'all';

        $query = StudentNotification::query()->where('user_id', $request->user()->id);

        if ($filter === 'unread') {
            $query->unread();
        } elseif ($filter === 'evaluation') {
            $query->whereIn('type', [
                StudentNotification::TYPE_EVALUATION_OPEN,
                StudentNotification::TYPE_EVALUATION_REMINDER,
                StudentNotification::TYPE_EVALUATION_CLOSED,
            ]);
        } elseif ($filter === 'system') {
            $query->where('type', StudentNotification::TYPE_SYSTEM_ACTIVITY);
        }

        $notifications = $query->latest()->paginate(15)->withQueryString();

        return view('student.notifications.index', compact('notifications', 'filter'));
    }

    public function read(Request $request, StudentNotification $notification)
    {
        Gate::authorize('update', $notification);
        $notification->update(['is_read' => true]);

        if ($notification->action_url && str_starts_with($notification->action_url, '/')) {
            return redirect()->to($notification->action_url);
        }

        return back()->with('status', 'Đã đánh dấu thông báo là đã đọc.');
    }

    public function readAll(Request $request)
    {
        Gate::authorize('viewAny', StudentNotification::class);
        StudentNotification::query()
            ->where('user_id', $request->user()->id)
            ->unread()
            ->update(['is_read' => true, 'updated_at' => now()]);

        return back()->with('status', 'Đã đánh dấu tất cả thông báo là đã đọc.');
    }
}
