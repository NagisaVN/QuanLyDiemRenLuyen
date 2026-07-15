<?php

namespace Database\Seeders;

use App\Models\ChiTietDanhGia;
use App\Models\DotDanhGia;
use App\Models\HoatDong;
use App\Models\HocKy;
use App\Models\Khoa;
use App\Models\Lop;
use App\Models\MucTieuChi;
use App\Models\NamHoc;
use App\Models\PhieuDanhGia;
use App\Models\SinhVien;
use App\Models\ThongBao;
use App\Models\User;
use App\Support\DrlRubric;
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
            'manage all activities', 'view audit logs', 'manage backups',
            'self evaluate', 'review class forms', 'approve final scores', 'export reports',
            'manage_dot_danh_gia', 'open_dot_danh_gia', 'close_dot_danh_gia', 'publish_dot_danh_gia',
            'view student notifications', 'manage notifications',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        Role::findByName('admin')->syncPermissions(Permission::all());
        Role::findByName('sinh_vien')->syncPermissions(['self evaluate', 'view student notifications']);
        Role::findByName('gvcn')->syncPermissions(['review class forms']);
        Role::findByName('can_bo_doan_hoi')->syncPermissions(['manage activities']);
        Role::findByName('hoi_dong_khoa')->syncPermissions([
            'approve final scores',
            'export reports',
            'manage_dot_danh_gia',
            'open_dot_danh_gia',
            'close_dot_danh_gia',
            'publish_dot_danh_gia',
        ]);

        $admin = $this->user('Quản trị hệ thống', 'admin@school.test', 'admin', 'admin');
        $gvcn = $this->user('Nguyễn Văn Cố Vấn', 'gvcn@school.test', 'gvcn01', 'gvcn');
        $doan = $this->user('Trần Thị Đoàn Hội', 'doanhoi@school.test', 'doanhoi01', 'can_bo_doan_hoi');
        $hoiDong = $this->user('Phạm Minh Công Tác', 'ctsv@school.test', 'ctsv01', 'hoi_dong_khoa');

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

        DrlRubric::sync();

        $activity = HoatDong::firstOrCreate([
            'ma_hoat_dong' => 'HD001',
        ], [
            'user_id' => $doan->id,
            'ten_hoat_dong' => 'Ngày hội kỹ năng sinh viên',
            'loai_hoat_dong' => 'Kỹ năng mềm',
            'mo_ta' => 'Hoạt động rèn luyện kỹ năng làm việc nhóm và thuyết trình.',
            'dia_diem' => '12 Trịnh Đình Thảo, Tân Phú',
            'location_lat' => null,
            'location_lng' => null,
            'location_radius_meters' => 100,
            'open_registration_at' => now()->subDay(),
            'close_registration_at' => now()->addDays(4),
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
        $dotDanhGia = DotDanhGia::updateOrCreate([
            'hoc_ky_id' => $activeHocKy->id,
            'ten_dot' => 'Đợt đánh giá học kỳ 3 năm học 2025-2026',
        ], [
            'nam_hoc_id' => $namHoc->id,
            'ngay_bat_dau_sinh_vien' => now()->subDays(2),
            'ngay_ket_thuc_sinh_vien' => now()->addWeeks(2),
            'ngay_bat_dau_gvcn' => now(),
            'ngay_ket_thuc_gvcn' => now()->addWeeks(3),
            'ngay_cong_bo' => now()->addMonth(),
            'trang_thai' => 'open',
            'mo_ta' => 'Đợt mẫu để sinh viên tự đánh giá và GVCN duyệt theo thời hạn.',
            'is_system_sample' => true,
            'created_by' => $admin->id,
            'updated_by' => $hoiDong->id,
        ]);
        $sampleStudent = SinhVien::where('ma_sinh_vien', 'SV002')->first();
        $phieu = PhieuDanhGia::firstOrCreate([
            'sinh_vien_id' => $sampleStudent->id,
            'hoc_ky_id' => $activeHocKy->id,
        ], [
            'dot_danh_gia_id' => $dotDanhGia->id,
            'trang_thai' => 'submitted',
            'submitted_at' => now(),
            'diem_tu_cham' => 75,
            'xep_loai' => 'Khá',
        ]);
        $phieu->update(['dot_danh_gia_id' => $dotDanhGia->id]);
        foreach (MucTieuChi::query()->where('loai', 'item')->where('is_active', true)->get() as $mucTieuChi) {
            ChiTietDanhGia::firstOrCreate([
                'phieu_danh_gia_id' => $phieu->id,
                'muc_tieu_chi_id' => $mucTieuChi->id,
            ], [
                'tieu_chi_id' => $mucTieuChi->tieu_chi_id,
                'diem_tu_cham' => max(0, min(2, (int) $mucTieuChi->diem_toi_da)),
            ]);
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
