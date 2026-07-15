<?php

namespace Tests\Feature;

use App\Models\HoatDong;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SecurityAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_staff_cannot_manage_another_staff_members_activity(): void
    {
        $role = Role::create(['name' => 'can_bo_doan_hoi', 'guard_name' => 'web']);
        $owner = User::factory()->create(['is_active' => true]);
        $intruder = User::factory()->create(['is_active' => true]);
        $owner->assignRole($role);
        $intruder->assignRole($role);

        $activity = HoatDong::create([
            'user_id' => $owner->id,
            'ma_hoat_dong' => 'SEC-01',
            'ten_hoat_dong' => 'Owner only',
            'loai_hoat_dong' => 'Test',
            'diem_cong' => 1,
            'trang_thai' => 'draft',
            'location_radius_meters' => 100,
        ]);

        $this->actingAs($intruder)->get(route('doan-hoi.activities.edit', $activity))->assertForbidden();
        $this->actingAs($intruder)->post(route('doan-hoi.activities.cancel', $activity))->assertForbidden();
        $this->assertNotNull($activity->fresh());
    }

    public function test_custom_role_can_access_a_capability_without_a_hard_coded_role_name(): void
    {
        $permission = Permission::findOrCreate('manage activities', 'web');
        $role = Role::create(['name' => 'event_operator', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        $this->actingAs($user)->get(route('doan-hoi.dashboard'))->assertOk();
    }

    public function test_admin_soft_deletes_and_restores_a_user_and_audits_both_actions(): void
    {
        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole($adminRole);
        $target = User::factory()->create(['is_active' => true]);

        $this->actingAs($admin)->delete(route('admin.crud.destroy', ['users', $target->id]))->assertRedirect();
        $this->assertSoftDeleted('users', ['id' => $target->id]);
        $this->assertDatabaseHas('logs', ['hanh_dong' => 'admin.deleted', 'doi_tuong_id' => $target->id]);

        $this->actingAs($admin)->post(route('admin.users.restore', $target->id))->assertRedirect();
        $this->assertNotSoftDeleted('users', ['id' => $target->id]);
        $this->assertDatabaseHas('logs', ['hanh_dong' => 'admin.restored', 'doi_tuong_id' => $target->id]);
    }

    public function test_audit_log_crud_is_read_only(): void
    {
        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole($adminRole);

        $this->actingAs($admin)->post(route('admin.crud.store', 'logs'), [])->assertForbidden();
    }

    public function test_last_role_administrator_cannot_be_deleted(): void
    {
        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole($adminRole);

        $this->actingAs($admin)->delete(route('admin.crud.destroy', ['users', $admin->id]))
            ->assertSessionHasErrors('user');
        $this->assertNotSoftDeleted('users', ['id' => $admin->id]);
    }
}
