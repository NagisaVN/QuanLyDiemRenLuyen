<?php

namespace App\Services;

use App\Models\AttendanceSession;
use App\Models\ConductPointLog;
use App\Models\DangKyHoatDong;
use App\Models\DiemDanhHoatDong;
use App\Models\HoatDong;
use App\Models\LichSuChinhSuaDiem;
use App\Models\SinhVien;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class HoatDongService
{
    private const MAX_GPS_ACCURACY_METERS = 100;

    public function __construct(private readonly DiemRenLuyenService $diemService) {}

    public function ensureQrToken(HoatDong $hoatDong): HoatDong
    {
        if (! $hoatDong->qr_token) {
            $hoatDong->update(['qr_token' => Str::random(48)]);
        }

        return $hoatDong->refresh();
    }

    public function register(HoatDong $hoatDong, SinhVien $sinhVien): DangKyHoatDong
    {
        return DB::transaction(function () use ($hoatDong, $sinhVien): DangKyHoatDong {
            $hoatDong = HoatDong::query()->lockForUpdate()->findOrFail($hoatDong->id);

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
        });
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

    public function createAttendanceSession(HoatDong $hoatDong, User $user, string $type, CarbonInterface $startAt, CarbonInterface $endAt): AttendanceSession
    {
        if (! in_array($type, ['check_in', 'check_out'], true)) {
            throw ValidationException::withMessages(['type' => 'Loại phiên điểm danh không hợp lệ.']);
        }

        if ($endAt->lessThanOrEqualTo($startAt)) {
            throw ValidationException::withMessages(['end_at' => 'Thời gian kết thúc phải sau thời gian bắt đầu.']);
        }

        return DB::transaction(function () use ($hoatDong, $user, $type, $startAt, $endAt) {
            AttendanceSession::query()
                ->where('hoat_dong_id', $hoatDong->id)
                ->where('type', $type)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            return AttendanceSession::create([
                'hoat_dong_id' => $hoatDong->id,
                'type' => $type,
                'token' => Str::random(64),
                'start_at' => $startAt,
                'end_at' => $endAt,
                'is_active' => true,
                'created_by' => $user->id,
            ]);
        });
    }

    public function scanAttendance(AttendanceSession $session, SinhVien $sinhVien, Request $request, array $position): DiemDanhHoatDong
    {
        $hoatDong = $session->hoatDong;
        $this->assertValidSession($session, (string) $request->input('token'));

        $registration = DangKyHoatDong::query()
            ->where('hoat_dong_id', $hoatDong->id)
            ->where('sinh_vien_id', $sinhVien->id)
            ->first();

        if (! $registration) {
            throw ValidationException::withMessages(['attendance' => 'Bạn chưa đăng ký hoạt động này.']);
        }

        if (! in_array($registration->trang_thai, ['approved', 'attended'], true)) {
            throw ValidationException::withMessages(['attendance' => 'Đăng ký hoạt động của bạn chưa được duyệt.']);
        }

        $distance = $this->distanceFromActivity($hoatDong, $position);

        return DB::transaction(function () use ($session, $hoatDong, $sinhVien, $registration, $request, $position, $distance) {
            $attendance = DiemDanhHoatDong::query()
                ->where('hoat_dong_id', $hoatDong->id)
                ->where('sinh_vien_id', $sinhVien->id)
                ->lockForUpdate()
                ->first();

            if (! $attendance) {
                $attendance = new DiemDanhHoatDong([
                    'hoat_dong_id' => $hoatDong->id,
                    'sinh_vien_id' => $sinhVien->id,
                    'dang_ky_hoat_dong_id' => $registration->id,
                    'phuong_thuc' => 'qr_gps',
                    'ip_address' => $request->ip(),
                    'status' => 'not_completed',
                    'point_awarded' => false,
                ]);
            }

            if ($session->type === 'check_in') {
                if ($attendance->checked_in_at) {
                    throw ValidationException::withMessages(['attendance' => 'Bạn đã điểm danh đầu giờ rồi.']);
                }

                $attendance->fill([
                    'dang_ky_hoat_dong_id' => $registration->id,
                    'checked_in_by' => $request->user()?->id,
                    'check_in_session_id' => $session->id,
                    'checked_in_at' => now(),
                    'check_in_lat' => $position['latitude'],
                    'check_in_lng' => $position['longitude'],
                    'check_in_distance_meters' => $distance,
                    'phuong_thuc' => 'qr_gps',
                    'ip_address' => $request->ip(),
                    'status' => 'not_completed',
                ])->save();

                return $attendance->refresh();
            }

            if (! $attendance->checked_in_at) {
                throw ValidationException::withMessages(['attendance' => 'Bạn cần điểm danh đầu giờ trước.']);
            }

            if ($attendance->check_out_time) {
                throw ValidationException::withMessages(['attendance' => 'Bạn đã điểm danh cuối giờ rồi.']);
            }

            $attendance->fill([
                'check_out_session_id' => $session->id,
                'check_out_time' => now(),
                'check_out_lat' => $position['latitude'],
                'check_out_lng' => $position['longitude'],
                'check_out_distance_meters' => $distance,
                'status' => 'completed',
                'ip_address' => $request->ip(),
            ])->save();

            $registration->update(['trang_thai' => 'attended']);

            return $attendance->refresh();
        });
    }

    public function checkIn(HoatDong $hoatDong, SinhVien $sinhVien, ?User $user, Request $request, string $method = 'qr'): DiemDanhHoatDong
    {
        $registration = DangKyHoatDong::query()
            ->where('hoat_dong_id', $hoatDong->id)
            ->where('sinh_vien_id', $sinhVien->id)
            ->first();

        if (! $registration) {
            throw ValidationException::withMessages(['dang_ky' => 'Sinh viên chưa đăng ký hoạt động này.']);
        }

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
                'check_out_time' => $method === 'manual' ? now() : null,
                'status' => $method === 'manual' ? 'completed' : 'not_completed',
                'ip_address' => $request->ip(),
            ]
        );

        if ($method === 'manual') {
            $registration->update(['trang_thai' => 'attended']);
        }

        return $attendance;
    }

    public function approveAttendance(HoatDong $hoatDong, User $user): int
    {
        return DB::transaction(function () use ($hoatDong, $user) {
            $records = DiemDanhHoatDong::query()
                ->where('hoat_dong_id', $hoatDong->id)
                ->whereIn('status', ['completed', 'pending_approval'])
                ->whereNotNull('checked_in_at')
                ->whereNotNull('check_out_time')
                ->where('point_awarded', false)
                ->lockForUpdate()
                ->get();

            $awarded = 0;

            foreach ($records as $record) {
                if ($hoatDong->auto_cong_diem) {
                    $log = ConductPointLog::firstOrCreate([
                        'sinh_vien_id' => $record->sinh_vien_id,
                        'hoat_dong_id' => $hoatDong->id,
                    ], [
                        'point' => $hoatDong->diem_cong,
                        'type' => 'activity',
                        'reason' => 'Cộng điểm sau khi đủ check-in và check-out: '.$hoatDong->ten_hoat_dong,
                    ]);

                    LichSuChinhSuaDiem::firstOrCreate([
                        'sinh_vien_id' => $record->sinh_vien_id,
                        'hoc_ky_id' => $this->diemService->activeHocKy()?->id,
                        'nguon' => 'hoat_dong',
                        'noi_dung' => $hoatDong->ten_hoat_dong,
                    ], [
                        'user_id' => $user->id,
                        'diem_cu' => 0,
                        'diem_moi' => $log->point,
                        'ly_do' => 'Cộng điểm sau khi đủ điểm danh đầu giờ và cuối giờ.',
                        'metadata' => ['hoat_dong_id' => $hoatDong->id],
                    ]);
                }

                $record->update([
                    'status' => 'approved',
                    'point_awarded' => $hoatDong->auto_cong_diem,
                ]);

                $awarded++;
            }

            return $awarded;
        });
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

    private function assertValidSession(AttendanceSession $session, string $token): void
    {
        if (! $session->is_active || ! hash_equals($session->token, $token)) {
            throw ValidationException::withMessages(['attendance' => 'QR không hợp lệ hoặc đã bị đóng.']);
        }

        if (now()->lt($session->start_at) || now()->gt($session->end_at)) {
            throw ValidationException::withMessages(['attendance' => 'QR đã hết hạn hoặc chưa đến khung giờ điểm danh.']);
        }
    }

    private function distanceFromActivity(HoatDong $hoatDong, array $position): int
    {
        if ($hoatDong->location_lat === null || $hoatDong->location_lng === null) {
            throw ValidationException::withMessages(['location' => 'Hoạt động chưa cấu hình tọa độ điểm danh.']);
        }

        if (($position['accuracy'] ?? null) !== null && (float) $position['accuracy'] > self::MAX_GPS_ACCURACY_METERS) {
            throw ValidationException::withMessages(['location' => 'Tín hiệu GPS chưa đủ chính xác, vui lòng bật định vị chính xác hơn.']);
        }

        $distance = $this->haversineDistanceMeters(
            (float) $hoatDong->location_lat,
            (float) $hoatDong->location_lng,
            (float) $position['latitude'],
            (float) $position['longitude']
        );

        if ($distance > (int) ($hoatDong->location_radius_meters ?: 100)) {
            throw ValidationException::withMessages(['location' => 'Bạn đang ở ngoài khu vực điểm danh.']);
        }

        return (int) round($distance);
    }

    private function haversineDistanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
