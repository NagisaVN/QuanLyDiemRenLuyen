<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\HoatDong;
use App\Services\HoatDongService;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $sinhVien = $request->user()->sinhVien;
        $activities = HoatDong::withCount(['dangKyHoatDongs as so_da_dang_ky'])
            ->where('trang_thai', 'open')
            ->latest()
            ->paginate(12);
        $registeredIds = $sinhVien?->hoatDongs()->pluck('hoat_dongs.id')->all() ?? [];

        return view('student.activities.index', compact('activities', 'registeredIds'));
    }

    public function register(Request $request, HoatDong $hoatDong, HoatDongService $service)
    {
        $service->register($hoatDong, $request->user()->sinhVien);

        return back()->with('status', 'Đã đăng ký hoạt động, vui lòng chờ duyệt.');
    }

    public function checkIn(Request $request, HoatDong $hoatDong, HoatDongService $service)
    {
        abort_unless(hash_equals((string) $hoatDong->qr_token, (string) $request->query('token')), 403);

        $service->checkIn($hoatDong, $request->user()->sinhVien, $request->user(), $request);

        return redirect()->route('sinh-vien.activities.index')->with('status', 'Điểm danh QR thành công.');
    }
}
