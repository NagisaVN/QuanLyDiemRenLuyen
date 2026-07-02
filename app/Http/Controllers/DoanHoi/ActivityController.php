<?php

namespace App\Http\Controllers\DoanHoi;

use App\Http\Controllers\Controller;
use App\Models\DangKyHoatDong;
use App\Models\HoatDong;
use App\Models\Khoa;
use App\Models\SinhVien;
use App\Models\TieuChi;
use App\Services\HoatDongService;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index()
    {
        $activities = HoatDong::withCount(['dangKyHoatDongs', 'diemDanhHoatDongs'])->latest()->paginate(15);

        return view('doan-hoi.activities.index', compact('activities'));
    }

    public function create()
    {
        return view('doan-hoi.activities.form', $this->formData(new HoatDong));
    }

    public function store(Request $request, HoatDongService $service)
    {
        $activity = HoatDong::create($this->validated($request) + ['user_id' => $request->user()->id]);
        $activity->khoas()->sync($request->input('khoa_ids', []));
        $service->ensureQrToken($activity);

        return redirect()->route('doan-hoi.activities.index')->with('status', 'Đã tạo hoạt động.');
    }

    public function edit(HoatDong $hoatDong)
    {
        return view('doan-hoi.activities.form', $this->formData($hoatDong));
    }

    public function update(Request $request, HoatDong $hoatDong, HoatDongService $service)
    {
        $hoatDong->update($this->validated($request));
        $hoatDong->khoas()->sync($request->input('khoa_ids', []));
        $service->ensureQrToken($hoatDong);

        return redirect()->route('doan-hoi.activities.index')->with('status', 'Đã cập nhật hoạt động.');
    }

    public function destroy(HoatDong $hoatDong)
    {
        $hoatDong->delete();

        return back()->with('status', 'Đã xóa hoạt động.');
    }

    public function registrations(HoatDong $hoatDong)
    {
        $registrations = $hoatDong->dangKyHoatDongs()->with('sinhVien.lop')->paginate(20);

        return view('doan-hoi.activities.registrations', compact('hoatDong', 'registrations'));
    }

    public function approve(Request $request, DangKyHoatDong $registration, HoatDongService $service)
    {
        $data = $request->validate(['trang_thai' => ['required', 'in:approved,rejected,cancelled']]);
        $service->approve($registration, $request->user(), $data['trang_thai']);

        return back()->with('status', 'Đã cập nhật đăng ký.');
    }

    public function attendance(Request $request, HoatDong $hoatDong, HoatDongService $service)
    {
        $data = $request->validate(['ma_sinh_vien' => ['required', 'exists:sinh_viens,ma_sinh_vien']]);
        $sinhVien = SinhVien::where('ma_sinh_vien', $data['ma_sinh_vien'])->firstOrFail();
        $service->checkIn($hoatDong, $sinhVien, $request->user(), $request, 'manual');

        return back()->with('status', 'Đã điểm danh sinh viên.');
    }

    public function qr(HoatDong $hoatDong, HoatDongService $service)
    {
        $hoatDong->load([
            'attendanceSessions' => fn ($query) => $query->latest(),
            'diemDanhHoatDongs.sinhVien.lop',
        ]);

        return view('doan-hoi.activities.qr', [
            'hoatDong' => $hoatDong,
            'sessions' => $hoatDong->attendanceSessions,
            'records' => $hoatDong->diemDanhHoatDongs,
        ]);
    }

    public function manualAdjust(Request $request, HoatDong $hoatDong, HoatDongService $service)
    {
        $data = $request->validate([
            'ma_sinh_vien' => ['required', 'exists:sinh_viens,ma_sinh_vien'],
            'points' => ['required', 'integer', 'between:-20,20'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $sinhVien = SinhVien::where('ma_sinh_vien', $data['ma_sinh_vien'])->firstOrFail();
        $service->manualAdjust($hoatDong, $sinhVien, $request->user(), (int) $data['points'], $data['reason']);

        return back()->with('status', 'Đã ghi nhận cộng/trừ điểm thủ công.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'tieu_chi_id' => ['nullable', 'exists:tieu_chis,id'],
            'ma_hoat_dong' => ['required', 'string', 'max:50'],
            'ten_hoat_dong' => ['required', 'string', 'max:255'],
            'loai_hoat_dong' => ['required', 'string', 'max:100'],
            'mo_ta' => ['nullable', 'string'],
            'dia_diem' => ['nullable', 'string', 'max:255'],
            'location_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'location_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'location_radius_meters' => ['nullable', 'integer', 'min:10', 'max:1000'],
            'thoi_gian_bat_dau' => ['nullable', 'date'],
            'thoi_gian_ket_thuc' => ['nullable', 'date'],
            'so_luong_toi_da' => ['nullable', 'integer', 'min:1'],
            'diem_cong' => ['required', 'integer', 'between:-20,20'],
            'trang_thai' => ['required', 'in:draft,open,closed,cancelled'],
        ]);

        $data['auto_cong_diem'] = $request->boolean('auto_cong_diem');
        $data['is_bat_buoc'] = $request->boolean('is_bat_buoc');
        $data['dia_diem'] = $data['dia_diem'] ?: '12 Trịnh Đình Thảo, Tân Phú';
        $data['location_radius_meters'] = $data['location_radius_meters'] ?: 100;

        return $data;
    }

    private function formData(HoatDong $hoatDong): array
    {
        return [
            'hoatDong' => $hoatDong,
            'khoas' => Khoa::orderBy('ten_khoa')->get(),
            'tieuChis' => TieuChi::orderBy('thu_tu')->get(),
            'types' => [
                'Hoạt động học tập',
                'Hoạt động Đoàn - Hội',
                'Văn nghệ - thể thao',
                'Cộng đồng - xã hội',
                'Kỹ năng mềm',
                'Đại diện sinh viên',
                'Thành tích đặc biệt',
                'Hoạt động bắt buộc',
            ],
        ];
    }
}
