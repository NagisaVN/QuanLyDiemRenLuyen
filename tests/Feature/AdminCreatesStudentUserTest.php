<?php

namespace Tests\Feature;

use App\Models\HocKy;
use App\Models\Khoa;
use App\Models\Lop;
use App\Models\NamHoc;
use App\Models\TieuChi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminCreatesStudentUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_student_user_with_student_profile(): void
    {
        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $studentRole = Role::create(['name' => 'sinh_vien', 'guard_name' => 'web']);

        $admin = User::factory()->create([
            'name' => 'Quản trị hệ thống',
            'email' => 'admin@example.test',
            'ma_dang_nhap' => 'admin',
            'is_active' => true,
        ]);
        $admin->assignRole($adminRole);

        $khoa = Khoa::create([
            'ma_khoa' => 'CNTT',
            'ten_khoa' => 'Công nghệ thông tin',
            'is_active' => true,
        ]);
        $lop = Lop::create([
            'khoa_id' => $khoa->id,
            'ma_lop' => 'D21CNTT01',
            'ten_lop' => 'D21CNTT01',
            'is_active' => true,
        ]);
        HocKy::create([
            'nam_hoc_id' => $this->createNamHocId(),
            'ten_hoc_ky' => 'Học kỳ kiểm thử',
            'thu_tu' => 1,
            'is_active' => true,
        ]);
        TieuChi::create([
            'ma_tieu_chi' => 'TC01',
            'ten_tieu_chi' => 'Ý thức học tập',
            'diem_toi_da' => 20,
            'thu_tu' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.crud.store', 'users'), [
            'name' => 'Nguyễn Sinh Viên',
            'email' => 'sv999@example.test',
            'password' => 'password',
            'is_active' => '1',
            'roles' => [$studentRole->id],
            'student' => [
                'lop_id' => $lop->id,
                'ma_sinh_vien' => 'SV999',
                'ho_ten' => 'Nguyễn Sinh Viên',
                'trang_thai' => 'dang_hoc',
            ],
        ]);

        $response->assertRedirect(route('admin.crud.index', 'users'));

        $studentUser = User::where('email', 'sv999@example.test')->firstOrFail();
        $this->assertTrue($studentUser->hasRole('sinh_vien'));
        $this->assertSame('SV999', $studentUser->ma_dang_nhap);
        $this->assertDatabaseHas('sinh_viens', [
            'user_id' => $studentUser->id,
            'lop_id' => $lop->id,
            'ma_sinh_vien' => 'SV999',
        ]);

        $this->post('/logout');

        $this->post('/login', [
            'login' => 'SV999',
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($studentUser);

        $this->actingAs($studentUser)
            ->get(route('sinh-vien.evaluations.index'))
            ->assertOk();
    }

    private function createNamHocId(): int
    {
        return NamHoc::create([
            'ten_nam_hoc' => '2026-2027',
            'is_active' => true,
        ])->id;
    }
}
