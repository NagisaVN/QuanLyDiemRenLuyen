<?php

namespace App\Http\Controllers\DoanHoi;

use App\Http\Controllers\Controller;
use App\Models\HoatDong;
use App\Models\Khoa;
use App\Models\SinhVien;
use App\Models\TieuChi;
use App\Services\AuditLogger;
use App\Services\ActivityLifecycleService;
use App\Services\HoatDongService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ActivityController extends Controller
{
    private const DEFAULT_ACTIVITY_LOCATION = [
        'lat' => 10.7749241,
        'lng' => 106.6345254,
    ];

    public function index(Request $request)
    {
        $activities = HoatDong::query()
            ->when(! $request->user()->can('manage all activities'), fn ($query) => $query->where('user_id', $request->user()->id))
            ->withCount([
                'dangKyHoatDongs' => fn ($query) => $query->whereIn('trang_thai', ['approved', 'completed']),
                'diemDanhHoatDongs',
            ])->latest()->paginate(15);

        return view('doan-hoi.activities.index', compact('activities'));
    }

    public function create()
    {
        return view('doan-hoi.activities.form', $this->formData(new HoatDong));
    }

    public function store(Request $request, HoatDongService $service)
    {
        $activity = HoatDong::create($this->validated($request, true) + [
            'user_id' => $request->user()->id,
            'trang_thai' => HoatDong::STATUS_SCHEDULED,
        ]);
        $activity->khoas()->sync($request->input('khoa_ids', []));
        $service->ensureQrToken($activity);
        app(AuditLogger::class)->write('activity.created', $activity);

        return redirect()->route('doan-hoi.activities.index')->with('status', 'Đã tạo hoạt động.');
    }

    public function edit(Request $request, HoatDong $hoatDong)
    {
        $this->authorizeActivity($request, $hoatDong);

        return view('doan-hoi.activities.form', $this->formData($hoatDong));
    }

    public function update(Request $request, HoatDong $hoatDong, HoatDongService $service)
    {
        $this->authorizeActivity($request, $hoatDong);
        $data = $this->validated($request);
        $scheduleFields = ['open_registration_at', 'close_registration_at', 'thoi_gian_bat_dau', 'thoi_gian_ket_thuc'];
        $scheduleBefore = $hoatDong->only($scheduleFields);
        $scheduleEditable = in_array($hoatDong->trang_thai, [
            HoatDong::STATUS_DRAFT,
            HoatDong::STATUS_SCHEDULED,
            HoatDong::STATUS_OPEN,
        ], true);

        if (! $scheduleEditable) {
            unset($data['open_registration_at'], $data['close_registration_at'], $data['thoi_gian_bat_dau'], $data['thoi_gian_ket_thuc']);
        } elseif (in_array($hoatDong->trang_thai, [HoatDong::STATUS_DRAFT, HoatDong::STATUS_SCHEDULED], true)) {
            $data['trang_thai'] = HoatDong::STATUS_SCHEDULED;
        } elseif ($data['open_registration_at']->isFuture()) {
            throw ValidationException::withMessages([
                'open_registration_at' => 'Hoạt động đã mở nên thời gian mở đăng ký không thể chuyển sang tương lai.',
            ]);
        }

        $hoatDong->update($data);
        $hoatDong->khoas()->sync($request->input('khoa_ids', []));
        $service->ensureQrToken($hoatDong);
        app(ActivityLifecycleService::class)->sync($hoatDong, 'schedule_updated');
        $hoatDong->refresh();
        app(AuditLogger::class)->write('activity.updated', $hoatDong, [
            'schedule_before' => $scheduleBefore,
            'schedule_after' => $hoatDong->only($scheduleFields),
        ]);

        return redirect()->route('doan-hoi.activities.index')->with('status', 'Đã cập nhật hoạt động.');
    }

    public function cancel(Request $request, HoatDong $hoatDong, ActivityLifecycleService $service)
    {
        $this->authorizeActivity($request, $hoatDong);
        $service->cancel($hoatDong, $request->user());

        return back()->with('status', 'Đã hủy hoạt động.');
    }

    public function registrations(Request $request, HoatDong $hoatDong)
    {
        $this->authorizeActivity($request, $hoatDong);
        $registrations = $hoatDong->dangKyHoatDongs()->with('sinhVien.lop')->paginate(20);

        return view('doan-hoi.activities.registrations', compact('hoatDong', 'registrations'));
    }

    public function attendance(Request $request, HoatDong $hoatDong, HoatDongService $service)
    {
        $this->authorizeActivity($request, $hoatDong);
        $data = $request->validate(['ma_sinh_vien' => ['required', 'exists:sinh_viens,ma_sinh_vien']]);
        $sinhVien = SinhVien::where('ma_sinh_vien', $data['ma_sinh_vien'])->firstOrFail();
        $service->checkIn($hoatDong, $sinhVien, $request->user(), $request, 'manual');

        return back()->with('status', 'Đã điểm danh sinh viên.');
    }

    public function qr(Request $request, HoatDong $hoatDong, HoatDongService $service)
    {
        $this->authorizeActivity($request, $hoatDong);
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
        $this->authorizeActivity($request, $hoatDong);
        $data = $request->validate([
            'ma_sinh_vien' => ['required', 'exists:sinh_viens,ma_sinh_vien'],
            'points' => ['required', 'integer', 'between:-20,20'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $sinhVien = SinhVien::where('ma_sinh_vien', $data['ma_sinh_vien'])->firstOrFail();
        $service->manualAdjust($hoatDong, $sinhVien, $request->user(), (int) $data['points'], $data['reason']);

        return back()->with('status', 'Đã ghi nhận cộng/trừ điểm thủ công.');
    }

    private function authorizeActivity(Request $request, HoatDong $hoatDong): void
    {
        abort_unless($request->user()->can('manage all activities') || $hoatDong->user_id === $request->user()->id, 403);
    }

    private function validated(Request $request, bool $creating = false): array
    {
        $validator = Validator::make($request->all(), [
            'tieu_chi_id' => ['nullable', 'exists:tieu_chis,id'],
            'ma_hoat_dong' => ['required', 'string', 'max:50'],
            'ten_hoat_dong' => ['required', 'string', 'max:255'],
            'loai_hoat_dong' => ['required', 'string', 'max:100'],
            'mo_ta' => ['nullable', 'string'],
            'dia_diem' => ['nullable', 'string', 'max:255'],
            'location_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'location_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'location_radius_meters' => ['required', 'integer', 'min:10', 'max:1000'],
            'open_registration_at' => ['required', 'date'],
            'close_registration_at' => ['required', 'date', 'after:open_registration_at'],
            'thoi_gian_bat_dau' => ['required', 'date', 'after_or_equal:close_registration_at'],
            'thoi_gian_ket_thuc' => ['required', 'date', 'after:thoi_gian_bat_dau'],
            'so_luong_toi_da' => ['nullable', 'integer', 'min:1'],
            'diem_cong' => ['required', 'integer', 'between:-20,20'],
        ]);

        $validator->after(function ($validator) use ($request, $creating) {
            if (! filled($request->input('location_lat')) || ! filled($request->input('location_lng'))) {
                $validator->errors()->add('location_lat', 'Vui lòng chọn vị trí trên bản đồ trước khi lưu hoạt động.');
            }

            if ($creating && filled($request->input('open_registration_at'))
                && Carbon::parse($request->input('open_registration_at'), config('app.display_timezone'))->isPast()) {
                $validator->errors()->add('open_registration_at', 'Thời gian mở đăng ký không được ở trong quá khứ.');
            }
        });

        $data = $validator->validate();

        $data['auto_cong_diem'] = $request->boolean('auto_cong_diem');
        $data['is_bat_buoc'] = $request->boolean('is_bat_buoc');
        $data['dia_diem'] = ($data['dia_diem'] ?? null) ?: '12 Trịnh Đình Thảo, Tân Phú';

        foreach (['open_registration_at', 'close_registration_at', 'thoi_gian_bat_dau', 'thoi_gian_ket_thuc'] as $field) {
            $data[$field] = Carbon::parse($data[$field], config('app.display_timezone'))->utc();
        }

        return $data;
    }

    private function formData(HoatDong $hoatDong): array
    {
        return [
            'hoatDong' => $hoatDong,
            'khoas' => Khoa::orderBy('ten_khoa')->get(),
            'tieuChis' => TieuChi::orderBy('thu_tu')->get(),
            'googleMapsBrowserKey' => config('services.google_maps.browser_key'),
            'defaultMapCenter' => self::DEFAULT_ACTIVITY_LOCATION,
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
