<?php

namespace App\Http\Controllers;

use App\Models\DangKyHoatDong;
use App\Models\DiemRenLuyen;
use App\Models\HoatDong;
use App\Models\PhieuDanhGia;
use App\Models\SinhVien;
use App\Services\DiemRenLuyenService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DashboardController extends Controller
{
    public function redirect(Request $request)
    {
        $user = $request->user();

        return match (true) {
            $user->hasRole('admin') => redirect()->route('admin.dashboard'),
            $user->hasRole('gvcn') => redirect()->route('gvcn.dashboard'),
            $user->hasRole('can_bo_doan_hoi') => redirect()->route('doan-hoi.dashboard'),
            $user->hasRole('hoi_dong_khoa') => redirect()->route('hoi-dong.dashboard'),
            default => redirect()->route('sinh-vien.dashboard'),
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

        if ($sinhVien) {
            try {
                $phieu = $service->ensurePhieu($sinhVien);
            } catch (ValidationException $exception) {
                $evaluationMessage = collect($exception->errors())->flatten()->first();
            }
        }

        return view('dashboards.sinh-vien', [
            ...$this->stats(),
            'sinhVien' => $sinhVien,
            'phieu' => $phieu,
            'evaluationMessage' => $evaluationMessage,
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

    public function doanHoi()
    {
        return view('dashboards.doan-hoi', [
            ...$this->stats(),
            'pendingRegistrations' => DangKyHoatDong::where('trang_thai', 'pending')->count(),
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
