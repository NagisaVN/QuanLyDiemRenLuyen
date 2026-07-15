<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ActivityManagerLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_keeps_admin_navigation_when_opening_activity_management(): void
    {
        $admin = $this->userWithActivityPermission('admin');

        $this->actingAs($admin)
            ->get(route('doan-hoi.activities.index'))
            ->assertOk()
            ->assertSee('<small class="text-muted">Quản trị viên</small>', false)
            ->assertSee('Người dùng')
            ->assertSee('Đợt đánh giá')
            ->assertSee('class="nav-link active" href="'.route('doan-hoi.activities.index').'"', false)
            ->assertDontSee('<small class="text-muted">Đoàn - Hội</small>', false);
    }

    public function test_doan_hoi_user_still_uses_doan_hoi_navigation(): void
    {
        $manager = $this->userWithActivityPermission('can_bo_doan_hoi');

        $this->actingAs($manager)
            ->get(route('doan-hoi.activities.index'))
            ->assertOk()
            ->assertSee('<small class="text-muted">Đoàn - Hội</small>', false)
            ->assertDontSee('Người dùng');
    }

    private function userWithActivityPermission(string $roleName): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permission = Permission::findOrCreate('manage activities', 'web');
        $role = Role::findOrCreate($roleName, 'web');
        $role->givePermissionTo($permission);
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        return $user;
    }
}
