<?php

namespace App\Http\Controllers;

use App\Models\DangKyHoatDong;
use App\Models\DiemRenLuyen;
use App\Models\HoatDong;
use App\Models\PhieuDanhGia;
use App\Models\SinhVien;
use App\Services\DiemRenLuyenService;
use App\Services\DotDanhGiaService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function home(Request $request)
    {
        if ($request->user()) {
            return redirect()->route('dashboard');
        }

        return view('welcome');
    }

    public function redirect(Request $request)
    {
        $user = $request->user();

        return match (true) {
            $user->canAny(['manage users', 'manage roles', 'manage master data', 'view audit logs', 'manage backups']) => redirect()->route('admin.dashboard'),
            $user->can('review class forms') => redirect()->route('gvcn.dashboard'),
            $user->can('manage activities') => redirect()->route('doan-hoi.dashboard'),
            $user->can('approve final scores') => redirect()->route('hoi-dong.dashboard'),
            $user->can('self evaluate') => redirect()->route('sinh-vien.dashboard'),
            default => abort(403, 'Tài khoản chưa được cấp quyền truy cập.'),
        };
    }

    public function admin()
    {
        return view('dashboards.admin', $this->stats());
    }

    public function sinhVien(Request $request, DiemRenLuyenService $service)
    {
        $sinhVien = $request->user()->sinhVien;
        $phieu = null;
        $evaluationMessage = null;
        $evaluationDeadlineAlert = null;

        if ($sinhVien) {
            $currentPeriod = app(DotDanhGiaService::class)->getCurrentStudentPeriod();
            $phieu = $currentPeriod
                ? PhieuDanhGia::query()
                    ->with('dotDanhGia')
                    ->where('sinh_vien_id', $sinhVien->id)
                    ->where('dot_danh_gia_id', $currentPeriod->id)
                    ->first()
                : null;
            $evaluationMessage = $currentPeriod ? null : 'Hiện chưa có đợt đánh giá đang mở.';

            if ($currentPeriod
                && $currentPeriod->ngay_ket_thuc_sinh_vien->lessThanOrEqualTo(now()->addDays(7))
                && (! $phieu || $phieu->trang_thai === PhieuDanhGia::STATUS_DRAFT)) {
                $evaluationDeadlineAlert = [
                    'period_name' => $currentPeriod->ten_dot,
                    'deadline' => $currentPeriod->displayDate($currentPeriod->ngay_ket_thuc_sinh_vien),
                ];
            }
        }

        return view('dashboards.sinh-vien', [
            ...$this->stats(),
            'sinhVien' => $sinhVien,
            'phieu' => $phieu,
            'evaluationMessage' => $evaluationMessage,
            'evaluationDeadlineAlert' => $evaluationDeadlineAlert,
        ]);
    }

    public function gvcn(Request $request)
    {
        $classIds = $request->user()->lopPhuTrachs()->pluck('id');

        return view('dashboards.gvcn', [
            ...$this->stats(),
            'lopCount' => $classIds->count(),
            'pendingClassForms' => PhieuDanhGia::whereHas('sinhVien', fn ($query) => $query->whereIn('lop_id', $classIds))
                ->where('trang_thai', 'submitted')
                ->count(),
        ]);
    }

    public function doanHoi(Request $request)
    {
        return view('dashboards.doan-hoi', [
            ...$this->stats(),
            'pendingRegistrations' => DangKyHoatDong::whereHas('hoatDong', function ($query) use ($request): void {
                if (! $request->user()->can('manage all activities')) {
                    $query->where('user_id', $request->user()->id);
                }
            })->where('trang_thai', 'pending')->count(),
        ]);
    }

    public function hoiDong()
    {
        return view('dashboards.hoi-dong', [
            ...$this->stats(),
            'reviewedForms' => PhieuDanhGia::where('trang_thai', 'reviewed')->count(),
        ]);
    }

    private function stats(): array
    {
        $rankGroups = DiemRenLuyen::selectRaw('xep_loai, count(*) as total')
            ->groupBy('xep_loai')
            ->pluck('total', 'xep_loai')
            ->toArray();

        return [
            'submittedForms' => PhieuDanhGia::whereIn('trang_thai', ['submitted', 'reviewed', 'approved', 'locked'])->count(),
            'pendingForms' => PhieuDanhGia::where('trang_thai', 'submitted')->count(),
            'openActivities' => HoatDong::where('trang_thai', 'open')->count(),
            'studentCount' => SinhVien::count(),
            'rankGroups' => $rankGroups,
        ];
    }
}
