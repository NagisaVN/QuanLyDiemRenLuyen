<?php

namespace App\Providers;

use App\Services\AuditLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
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
