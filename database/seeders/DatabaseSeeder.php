<?php

namespace Database\Seeders;

use App\Models\ChiTietDanhGia;
use App\Models\HoatDong;
use App\Models\HocKy;
use App\Models\Khoa;
use App\Models\Lop;
use App\Models\MucTieuChi;
use App\Models\NamHoc;
use App\Models\PhieuDanhGia;
use App\Models\SinhVien;
use App\Models\ThongBao;
use App\Models\TieuChi;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $roles = ['admin', 'sinh_vien', 'gvcn', 'can_bo_doan_hoi', 'hoi_dong_khoa'];
        $permissions = [
            'manage users', 'manage roles', 'manage master data', 'manage activities',
            'self evaluate', 'review class forms', 'approve final scores', 'export reports',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        Role::findByName('admin')->syncPermissions(Permission::all());
        Role::findByName('sinh_vien')->syncPermissions(['self evaluate']);
        Role::findByName('gvcn')->syncPermissions(['review class forms']);
        Role::findByName('can_bo_doan_hoi')->syncPermissions(['manage activities']);
        Role::findByName('hoi_dong_khoa')->syncPermissions(['approve final scores', 'export reports']);

        $admin = $this->user('Quản trị hệ thống', 'admin@school.test', 'admin', 'admin');
        $gvcn = $this->user('Nguyễn Văn Cố Vấn', 'gvcn@school.test', 'gvcn01', 'gvcn');
        $doan = $this->user('Trần Thị Đoàn Hội', 'doanhoi@school.test', 'doanhoi01', 'can_bo_doan_hoi');
        $hoiDong = $this->user('Phạm Minh Hội Đồng', 'hoidong@school.test', 'hoidong01', 'hoi_dong_khoa');

        $cntt = Khoa::firstOrCreate(['ma_khoa' => 'CNTT'], ['ten_khoa' => 'Công nghệ thông tin', 'is_active' => true]);
        $qtkd = Khoa::firstOrCreate(['ma_khoa' => 'QTKD'], ['ten_khoa' => 'Quản trị kinh doanh', 'is_active' => true]);

        $lop = Lop::firstOrCreate([
            'ma_lop' => 'D21CNTT01',
        ], [
            'ten_lop' => 'D21CNTT01',
            'khoa_id' => $cntt->id,
            'gvcn_id' => $gvcn->id,
            'nien_khoa' => '2021-2025',
            'is_active' => true,
        ]);

        $students = [
            ['Nguyễn An', 'SV001', 'sv001@school.test'],
            ['Lê Bình', 'SV002', 'sv002@school.test'],
            ['Trần Chi', 'SV003', 'sv003@school.test'],
        ];

        foreach ($students as [$name, $code, $email]) {
            $user = $this->user($name, $email, strtolower($code), 'sinh_vien');
            SinhVien::firstOrCreate([
                'ma_sinh_vien' => $code,
            ], [
                'user_id' => $user->id,
                'lop_id' => $lop->id,
                'ho_ten' => $name,
                'ngay_sinh' => '2003-01-01',
                'gioi_tinh' => 'Nam/Nữ',
                'trang_thai' => 'dang_hoc',
            ]);
        }

        $namHoc = NamHoc::firstOrCreate([
            'ten_nam_hoc' => '2025-2026',
        ], [
            'ngay_bat_dau' => '2025-09-01',
            'ngay_ket_thuc' => '2026-08-31',
            'is_active' => true,
        ]);

        $hocKys = [
            ['Học kỳ 1', 1, '2025-09-01', '2025-12-31', false],
            ['Học kỳ 2', 2, '2026-01-01', '2026-04-30', false],
            ['Học kỳ 3', 3, '2026-05-01', '2026-08-31', true],
        ];

        foreach ($hocKys as [$name, $order, $start, $end, $active]) {
            HocKy::updateOrCreate([
                'nam_hoc_id' => $namHoc->id,
                'thu_tu' => $order,
            ], [
                'ten_hoc_ky' => $name,
                'ngay_bat_dau' => $start,
                'ngay_ket_thuc' => $end,
                'han_tu_danh_gia' => now()->addWeeks(2),
                'han_gvcn_duyet' => now()->addWeeks(3),
                'han_hoi_dong_duyet' => now()->addWeeks(4),
                'ngay_cong_bo' => now()->addMonth(),
                'is_active' => $active,
            ]);
        }

        $criteria = [
            ['TC01', 'Ý thức học tập', 'Chuyên cần, thái độ học tập, kết quả rèn luyện học thuật'],
            ['TC02', 'Chấp hành nội quy', 'Tuân thủ quy chế, pháp luật, nội quy nhà trường'],
            ['TC03', 'Hoạt động chính trị - xã hội', 'Tham gia Đoàn - Hội, hoạt động lớp, khoa, trường'],
            ['TC04', 'Phẩm chất công dân', 'Quan hệ cộng đồng, tinh thần trách nhiệm và văn hóa ứng xử'],
            ['TC05', 'Công tác phụ trách và thành tích', 'Ban cán sự, đại diện sinh viên, thành tích đặc biệt'],
        ];

        foreach ($criteria as $index => [$code, $name, $description]) {
            $tieuChi = TieuChi::updateOrCreate([
                'ma_tieu_chi' => $code,
            ], [
                'ten_tieu_chi' => $name,
                'mo_ta' => $description,
                'diem_toi_da' => 20,
                'thu_tu' => $index + 1,
                'is_active' => true,
            ]);

            foreach ([5, 10, 15, 20] as $point) {
                MucTieuChi::firstOrCreate([
                    'tieu_chi_id' => $tieuChi->id,
                    'ten_muc' => "Mức {$point} điểm",
                ], [
                    'diem_toi_da' => $point,
                    'thu_tu' => $point,
                    'is_active' => true,
                ]);
            }
        }

        $activity = HoatDong::firstOrCreate([
            'ma_hoat_dong' => 'HD001',
        ], [
            'user_id' => $doan->id,
            'ten_hoat_dong' => 'Ngày hội kỹ năng sinh viên',
            'loai_hoat_dong' => 'Kỹ năng mềm',
            'mo_ta' => 'Hoạt động rèn luyện kỹ năng làm việc nhóm và thuyết trình.',
            'dia_diem' => 'Hội trường A',
            'thoi_gian_bat_dau' => now()->addDays(5),
            'thoi_gian_ket_thuc' => now()->addDays(5)->addHours(3),
            'so_luong_toi_da' => 100,
            'diem_cong' => 5,
            'trang_thai' => 'open',
            'qr_token' => Str::random(48),
            'auto_cong_diem' => true,
        ]);
        $activity->khoas()->sync([$cntt->id, $qtkd->id]);

        $activeHocKy = HocKy::where('is_active', true)->first();
        $sampleStudent = SinhVien::where('ma_sinh_vien', 'SV002')->first();
        $phieu = PhieuDanhGia::firstOrCreate([
            'sinh_vien_id' => $sampleStudent->id,
            'hoc_ky_id' => $activeHocKy->id,
        ], [
            'trang_thai' => 'submitted',
            'submitted_at' => now(),
            'diem_tu_cham' => 75,
            'xep_loai' => 'Khá',
        ]);
        foreach (TieuChi::all() as $tieuChi) {
            ChiTietDanhGia::firstOrCreate([
                'phieu_danh_gia_id' => $phieu->id,
                'tieu_chi_id' => $tieuChi->id,
            ], ['diem_tu_cham' => 15]);
        }

        ThongBao::firstOrCreate([
            'tieu_de' => 'Thông báo tự đánh giá điểm rèn luyện học kỳ 3',
        ], [
            'user_id' => $admin->id,
            'hoc_ky_id' => $activeHocKy->id,
            'noi_dung' => 'Sinh viên thực hiện tự đánh giá và nộp minh chứng trước hạn. Điểm sẽ được công bố trong vòng 1 tháng.',
            'loai' => 'tu_danh_gia',
            'doi_tuong' => 'sinh_vien',
            'published_at' => now(),
            'het_han_at' => now()->addWeeks(2),
            'is_active' => true,
        ]);
    }

    private function user(string $name, string $email, string $login, string $role): User
    {
        $user = User::updateOrCreate([
            'email' => $email,
        ], [
            'name' => $name,
            'ma_dang_nhap' => $login,
            'password' => 'password',
            'is_active' => true,
        ]);

        $user->assignRole($role);

        return $user;
    }
}
