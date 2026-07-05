<?php

namespace Database\Seeders;

use App\Models\DotDanhGia;
use App\Models\HocKy;
use App\Models\Khoa;
use App\Models\Lop;
use App\Models\NamHoc;
use App\Models\SinhVien;
use App\Models\User;
use App\Support\DrlRubric;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DrlWorkflowTestSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['sinh_vien', 'gvcn', 'hoi_dong_khoa'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $studentUser = $this->user('Nguyễn Sinh Viên', 'sv001@school.test', 'sv001', 'sinh_vien');
        $gvcnUser = $this->user('Giáo viên chủ nhiệm', 'gvcn01@school.test', 'gvcn01', 'gvcn');
        $ctsvUser = $this->user('Công tác sinh viên', 'ctsv01@school.test', 'ctsv01', 'hoi_dong_khoa');

        $khoa = Khoa::firstOrCreate(
            ['ma_khoa' => 'CNTT'],
            ['ten_khoa' => 'Công nghệ thông tin', 'is_active' => true],
        );

        $lop = Lop::updateOrCreate(
            ['ma_lop' => 'D21CNTT01'],
            [
                'ten_lop' => 'D21CNTT01',
                'khoa_id' => $khoa->id,
                'gvcn_id' => $gvcnUser->id,
                'nien_khoa' => '2021-2025',
                'is_active' => true,
            ],
        );

        SinhVien::updateOrCreate(
            ['ma_sinh_vien' => 'SV001'],
            [
                'user_id' => $studentUser->id,
                'lop_id' => $lop->id,
                'ho_ten' => 'Nguyễn Sinh Viên',
                'ngay_sinh' => '2003-01-01',
                'gioi_tinh' => 'Nam/Nữ',
                'trang_thai' => 'dang_hoc',
            ],
        );

        $namHoc = NamHoc::firstOrCreate(
            ['ten_nam_hoc' => '2025-2026'],
            [
                'ngay_bat_dau' => '2025-09-01',
                'ngay_ket_thuc' => '2026-08-31',
                'is_active' => true,
            ],
        );

        $hocKy = HocKy::updateOrCreate(
            ['nam_hoc_id' => $namHoc->id, 'thu_tu' => 3],
            [
                'ten_hoc_ky' => 'Học kỳ 3',
                'ngay_bat_dau' => '2026-05-01',
                'ngay_ket_thuc' => '2026-08-31',
                'han_tu_danh_gia' => now()->addWeeks(2),
                'han_gvcn_duyet' => now()->addWeeks(3),
                'han_hoi_dong_duyet' => now()->addWeeks(4),
                'ngay_cong_bo' => now()->addMonth(),
                'is_active' => true,
            ],
        );

        DotDanhGia::updateOrCreate(
            [
                'hoc_ky_id' => $hocKy->id,
                'ten_dot' => 'Đợt kiểm thử luồng điểm rèn luyện',
            ],
            [
                'nam_hoc_id' => $namHoc->id,
                'ngay_bat_dau_sinh_vien' => now()->subDay(),
                'ngay_ket_thuc_sinh_vien' => now()->addWeeks(2),
                'ngay_bat_dau_gvcn' => now()->subHour(),
                'ngay_ket_thuc_gvcn' => now()->addWeeks(3),
                'ngay_cong_bo' => now()->addMonth(),
                'trang_thai' => DotDanhGia::STATUS_OPEN,
                'mo_ta' => 'Dữ liệu an toàn để kiểm thử sv001 -> gvcn01 -> ctsv01.',
                'created_by' => $ctsvUser->id,
                'updated_by' => $ctsvUser->id,
            ],
        );

        DrlRubric::syncIfMissing();
    }

    private function user(string $name, string $email, string $login, string $role): User
    {
        $user = User::query()
            ->where('ma_dang_nhap', $login)
            ->orWhere('email', $email)
            ->first();

        if (! $user) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'ma_dang_nhap' => $login,
                'password' => 'password',
                'is_active' => true,
            ]);
        } else {
            $user->fill([
                'name' => $user->name ?: $name,
                'email' => $user->email ?: $email,
                'ma_dang_nhap' => $user->ma_dang_nhap ?: $login,
                'is_active' => true,
            ])->save();
        }

        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }

        return $user;
    }
}
