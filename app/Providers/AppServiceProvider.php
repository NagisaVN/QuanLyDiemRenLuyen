<?php

namespace App\Providers;

use App\Events\ActivityOpenedEvent;
use App\Events\ActivityOpeningSoonEvent;
use App\Events\EvaluationClosedEvent;
use App\Events\EvaluationClosingSoonEvent;
use App\Events\EvaluationOpenedEvent;
use App\Events\SystemAnnouncementPublishedEvent;
use App\Listeners\DistributeSystemAnnouncement;
use App\Listeners\SendActivityOpenedNotification;
use App\Listeners\SendActivityOpeningSoonNotification;
use App\Listeners\SendEvaluationClosedNotification;
use App\Listeners\SendEvaluationOpenedNotification;
use App\Listeners\SendEvaluationReminderNotification;
use App\Models\StudentNotification;
use App\Models\ThongBao;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();
        Model::preventLazyLoading(! app()->isProduction());

        Event::listen(Login::class, fn (Login $event) => app(AuditLogger::class)->write('auth.login', $event->user, actorId: (int) $event->user->getAuthIdentifier()));
        Event::listen(Logout::class, fn (Logout $event) => $event->user && app(AuditLogger::class)->write('auth.logout', $event->user, actorId: (int) $event->user->getAuthIdentifier()));
        Event::listen(Failed::class, fn (Failed $event) => app(AuditLogger::class)->write('auth.failed', 'user', ['identifier' => $event->credentials['email'] ?? $event->credentials['ma_dang_nhap'] ?? null], actorId: $event->user?->getAuthIdentifier()));
        Event::listen(Lockout::class, fn (Lockout $event) => app(AuditLogger::class)->write('auth.lockout', 'user', ['login' => $event->request->input('login')], request: $event->request));
        Event::listen(EvaluationOpenedEvent::class, SendEvaluationOpenedNotification::class);
        Event::listen(EvaluationClosingSoonEvent::class, SendEvaluationReminderNotification::class);
        Event::listen(EvaluationClosedEvent::class, SendEvaluationClosedNotification::class);
        Event::listen(ActivityOpenedEvent::class, SendActivityOpenedNotification::class);
        Event::listen(ActivityOpeningSoonEvent::class, SendActivityOpeningSoonNotification::class);
        Event::listen(SystemAnnouncementPublishedEvent::class, DistributeSystemAnnouncement::class);

        ThongBao::saved(function (ThongBao $announcement): void {
            if ($announcement->is_active && ! $announcement->distributed_at && (! $announcement->published_at || $announcement->published_at->isPast())) {
                SystemAnnouncementPublishedEvent::dispatch($announcement);
            }
        });

        View::composer('layouts.shell', function ($view): void {
            $user = auth()->user();
            $notifications = collect();
            $unreadCount = 0;

            if ($user?->can('view student notifications') && Schema::hasTable('notifications')) {
                $base = StudentNotification::query()->where('user_id', $user->id);
                $unreadCount = (clone $base)->unread()->count();
                $notifications = $base->latest()->limit(6)->get();
            }

            $view->with(compact('notifications', 'unreadCount'));
        });

        Role::created(function (Role $role): void {
            $defaults = config("rbac.default_roles.{$role->name}", []);
            if ($role->guard_name !== 'web' || ! $defaults) {
                return;
            }
            $permissions = collect($defaults)->map(fn (string $name) => Permission::findOrCreate($name, 'web'));
            $role->syncPermissions($permissions);
        });
    }
}
