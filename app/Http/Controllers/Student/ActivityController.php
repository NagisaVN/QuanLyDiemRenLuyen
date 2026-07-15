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
        $sinhVien = $request->user()->sinhVien?->loadMissing('lop');
        abort_unless($sinhVien?->lop, 403);
        $activities = HoatDong::query()
            ->with('creator')
            ->withCount(['dangKyHoatDongs as so_da_dang_ky' => fn ($query) => $query->whereIn('trang_thai', ['approved', 'completed'])])
            ->whereIn('trang_thai', [HoatDong::STATUS_SCHEDULED, HoatDong::STATUS_OPEN, HoatDong::STATUS_REGISTRATION_CLOSED, HoatDong::STATUS_COMPLETED])
            ->where(fn ($query) => $query->whereDoesntHave('khoas')->orWhereHas('khoas', fn ($faculties) => $faculties->whereKey($sinhVien->lop->khoa_id)))
            ->orderByRaw("CASE trang_thai WHEN 'open' THEN 1 WHEN 'scheduled' THEN 2 WHEN 'registration_closed' THEN 3 ELSE 4 END")
            ->orderBy('thoi_gian_bat_dau')
            ->paginate(12);
        $registeredIds = $sinhVien?->hoatDongs()->pluck('hoat_dongs.id')->all() ?? [];

        return view('student.activities.index', compact('activities', 'registeredIds'));
    }

    public function show(Request $request, HoatDong $hoatDong)
    {
        $sinhVien = $request->user()->sinhVien?->loadMissing('lop');
        abort_unless($sinhVien?->lop, 403);
        abort_if(in_array($hoatDong->trang_thai, [HoatDong::STATUS_DRAFT, HoatDong::STATUS_CANCELLED], true), 404);
        abort_if($hoatDong->khoas()->exists() && ! $hoatDong->khoas()->whereKey($sinhVien->lop->khoa_id)->exists(), 403);

        $hoatDong->load('creator')->loadCount([
            'dangKyHoatDongs as so_da_dang_ky' => fn ($query) => $query->whereIn('trang_thai', ['approved', 'completed']),
        ]);
        $registered = $hoatDong->dangKyHoatDongs()->where('sinh_vien_id', $sinhVien->id)->exists();

        return view('student.activities.show', compact('hoatDong', 'registered'));
    }

    public function register(Request $request, HoatDong $hoatDong, HoatDongService $service)
    {
        $registration = $service->register($hoatDong, $request->user()->sinhVien);
        $registeredCount = $service->registeredCount($hoatDong);
        $remainingSlots = $hoatDong->so_luong_toi_da === null ? null : max(0, $hoatDong->so_luong_toi_da - $registeredCount);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Bạn đã đăng ký thành công hoạt động này.',
                'registration' => ['id' => $registration->id, 'status' => $registration->trang_thai],
                'registered_count' => $registeredCount,
                'remaining_slots' => $remainingSlots,
            ]);
        }

        return back()->with('status', 'Bạn đã đăng ký thành công hoạt động này.');
    }

    public function checkIn(Request $request, HoatDong $hoatDong, HoatDongService $service)
    {
        abort_unless(hash_equals((string) $hoatDong->qr_token, (string) $request->query('token')), 403);

        $service->checkIn($hoatDong, $request->user()->sinhVien, $request->user(), $request);

        return redirect()->route('sinh-vien.activities.index')->with('status', 'Điểm danh QR thành công.');
    }

    public function scan(Request $request)
    {
        $data = $request->validate([
            'sessionId' => ['required', 'integer'],
            'token' => ['required', 'string'],
        ]);

        return view('student.activities.scan', $data);
    }
}
