<?php

namespace App\Services;

use App\Models\DangKyHoatDong;
use App\Models\DiemDanhHoatDong;
use App\Models\HoatDong;
use App\Models\LichSuChinhSuaDiem;
use App\Models\SinhVien;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class HoatDongService
{
    public function __construct(private readonly DiemRenLuyenService $diemService)
    {
    }

    public function ensureQrToken(HoatDong $hoatDong): HoatDong
    {
        if (! $hoatDong->qr_token) {
            $hoatDong->update(['qr_token' => Str::random(48)]);
        }

        return $hoatDong->refresh();
    }

    public function register(HoatDong $hoatDong, SinhVien $sinhVien): DangKyHoatDong
    {
        if ($hoatDong->trang_thai !== 'open') {
            throw ValidationException::withMessages(['hoat_dong' => 'Hoạt động chưa mở đăng ký.']);
        }

        if ($hoatDong->khoas()->exists() && ! $hoatDong->khoas()->whereKey($sinhVien->lop->khoa_id)->exists()) {
            throw ValidationException::withMessages(['hoat_dong' => 'Hoạt động không áp dụng cho khoa của sinh viên.']);
        }

        if ($hoatDong->so_luong_toi_da && $hoatDong->dangKyHoatDongs()->whereIn('trang_thai', ['pending', 'approved', 'attended'])->count() >= $hoatDong->so_luong_toi_da) {
            throw ValidationException::withMessages(['hoat_dong' => 'Hoạt động đã đủ số lượng.']);
        }

        return DangKyHoatDong::firstOrCreate(
            ['hoat_dong_id' => $hoatDong->id, 'sinh_vien_id' => $sinhVien->id],
            ['trang_thai' => 'pending']
        );
    }

    public function approve(DangKyHoatDong $registration, User $user, string $status = 'approved'): DangKyHoatDong
    {
        $registration->update([
            'trang_thai' => $status,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        return $registration->refresh();
    }

    public function checkIn(HoatDong $hoatDong, SinhVien $sinhVien, ?User $user, Request $request, string $method = 'qr'): DiemDanhHoatDong
    {
        $registration = DangKyHoatDong::firstOrCreate(
            ['hoat_dong_id' => $hoatDong->id, 'sinh_vien_id' => $sinhVien->id],
            ['trang_thai' => 'approved', 'approved_by' => $user?->id, 'approved_at' => now()]
        );

        if (! in_array($registration->trang_thai, ['approved', 'attended'], true)) {
            throw ValidationException::withMessages(['dang_ky' => 'Sinh viên chưa được duyệt tham gia.']);
        }

        $attendance = DiemDanhHoatDong::updateOrCreate(
            ['hoat_dong_id' => $hoatDong->id, 'sinh_vien_id' => $sinhVien->id],
            [
                'dang_ky_hoat_dong_id' => $registration->id,
                'checked_in_by' => $user?->id,
                'phuong_thuc' => $method,
                'checked_in_at' => now(),
                'ip_address' => $request->ip(),
            ]
        );

        $registration->update(['trang_thai' => 'attended']);

        if ($hoatDong->auto_cong_diem) {
            LichSuChinhSuaDiem::firstOrCreate([
                'sinh_vien_id' => $sinhVien->id,
                'hoc_ky_id' => $this->diemService->activeHocKy()?->id,
                'nguon' => 'hoat_dong',
                'noi_dung' => $hoatDong->ten_hoat_dong,
            ], [
                'user_id' => $user?->id,
                'diem_cu' => 0,
                'diem_moi' => $hoatDong->diem_cong,
                'ly_do' => 'Tự động cộng điểm sau điểm danh',
                'metadata' => ['hoat_dong_id' => $hoatDong->id],
            ]);
        }

        return $attendance;
    }

    public function manualAdjust(HoatDong $hoatDong, SinhVien $sinhVien, User $user, int $points, string $reason): void
    {
        LichSuChinhSuaDiem::create([
            'sinh_vien_id' => $sinhVien->id,
            'hoc_ky_id' => $this->diemService->activeHocKy()?->id,
            'user_id' => $user->id,
            'nguon' => 'hoat_dong_thu_cong',
            'diem_cu' => 0,
            'diem_moi' => $points,
            'noi_dung' => $hoatDong->ten_hoat_dong,
            'ly_do' => $reason,
            'metadata' => ['hoat_dong_id' => $hoatDong->id],
        ]);
    }
}
