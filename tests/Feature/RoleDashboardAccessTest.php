<?php

namespace Tests\Feature;

use App\Models\Khoa;
use App\Models\Lop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleDashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_dashboards_can_be_rendered(): void
    {
        $roles = $this->createRoles();

        $admin = $this->userWithRole('admin@example.test', 'admin', $roles['admin']);
        $gvcn = $this->userWithRole('gvcn@example.test', 'gvcn01', $roles['gvcn']);
        $doanHoi = $this->userWithRole('doanhoi@example.test', 'doanhoi01', $roles['can_bo_doan_hoi']);
        $hoiDong = $this->userWithRole('hoidong@example.test', 'hoidong01', $roles['hoi_dong_khoa']);

        $khoa = Khoa::create([
            'ma_khoa' => 'CNTT',
            'ten_khoa' => 'Công nghệ thông tin',
            'is_active' => true,
        ]);
        Lop::create([
            'khoa_id' => $khoa->id,
            'gvcn_id' => $gvcn->id,
            'ma_lop' => 'D21CNTT01',
            'ten_lop' => 'D21CNTT01',
            'is_active' => true,
        ]);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($gvcn)->get(route('gvcn.dashboard'))->assertOk();
        $this->actingAs($doanHoi)->get(route('doan-hoi.dashboard'))->assertOk();
        $this->actingAs($hoiDong)->get(route('hoi-dong.dashboard'))->assertOk();
    }

    public function test_admin_can_create_non_student_user_without_student_profile(): void
    {
        $roles = $this->createRoles();
        $admin = $this->userWithRole('admin@example.test', 'admin', $roles['admin']);

        $response = $this->actingAs($admin)->post(route('admin.crud.store', 'users'), [
            'name' => 'Cán bộ Đoàn Hội',
            'email' => 'canbo@example.test',
            'ma_dang_nhap' => 'canbo01',
            'password' => 'password',
            'is_active' => '1',
            'roles' => [$roles['can_bo_doan_hoi']->id],
        ]);

        $response->assertRedirect(route('admin.crud.index', 'users'));

        $user = User::where('email', 'canbo@example.test')->firstOrFail();
        $this->assertTrue($user->hasRole('can_bo_doan_hoi'));
        $this->assertDatabaseMissing('sinh_viens', [
            'user_id' => $user->id,
        ]);
    }

    private function createRoles(): array
    {
        return collect(['admin', 'sinh_vien', 'gvcn', 'can_bo_doan_hoi', 'hoi_dong_khoa'])
            ->mapWithKeys(fn (string $role) => [$role => Role::create(['name' => $role, 'guard_name' => 'web'])])
            ->all();
    }

    private function userWithRole(string $email, string $login, Role $role): User
    {
        $user = User::factory()->create([
            'email' => $email,
            'ma_dang_nhap' => $login,
            'is_active' => true,
        ]);

        $user->assignRole($role);

        return $user;
    }
}
