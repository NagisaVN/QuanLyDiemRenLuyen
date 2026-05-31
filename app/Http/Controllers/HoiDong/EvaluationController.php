<?php

namespace App\Http\Controllers\HoiDong;

use App\Exports\DiemRenLuyenExport;
use App\Http\Controllers\Controller;
use App\Models\PhieuDanhGia;
use App\Services\DiemRenLuyenService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class EvaluationController extends Controller
{
    public function index()
    {
        $forms = PhieuDanhGia::with(['sinhVien.lop.khoa', 'hocKy', 'dotDanhGia'])
            ->whereIn('trang_thai', ['reviewed', 'approved', 'locked'])
            ->latest()
            ->paginate(20);

        return view('hoi-dong.evaluations.index', compact('forms'));
    }

    public function show(PhieuDanhGia $phieu)
    {
        return view('hoi-dong.evaluations.show', ['phieu' => $phieu->load(['sinhVien.lop.khoa', 'hocKy', 'dotDanhGia', 'chiTietDanhGias.tieuChi', 'minhChungs'])]);
    }

    public function update(Request $request, PhieuDanhGia $phieu, DiemRenLuyenService $service)
    {
        $data = $request->validate([
            'scores' => ['required', 'array'],
            'nhan_xet_hoi_dong' => ['nullable', 'string', 'max:2000'],
        ]);

        $service->saveReviewerScores($phieu, $data['scores'], $request->user(), 'hoi_dong', $data['nhan_xet_hoi_dong'] ?? null);

        return back()->with('status', 'Đã lưu điểm hội đồng.');
    }

    public function approve(Request $request, PhieuDanhGia $phieu, DiemRenLuyenService $service)
    {
        if ($request->filled('scores')) {
            $data = $request->validate([
                'scores' => ['required', 'array'],
                'nhan_xet_hoi_dong' => ['nullable', 'string', 'max:2000'],
            ]);

            $service->saveReviewerScores($phieu, $data['scores'], $request->user(), 'hoi_dong', $data['nhan_xet_hoi_dong'] ?? null);
            $phieu->refresh();
        }

        $service->approveFinal($phieu, $request->user(), $request->input('nhan_xet_hoi_dong'));

        return back()->with('status', 'Đã xác nhận điểm cuối cùng.');
    }

    public function lock(Request $request, PhieuDanhGia $phieu, DiemRenLuyenService $service)
    {
        $service->lock($phieu, $request->user());

        return back()->with('status', 'Đã khóa phiếu.');
    }

    public function exportExcel()
    {
        return Excel::download(new DiemRenLuyenExport, 'diem-ren-luyen.xlsx');
    }

    public function exportPdf()
    {
        $forms = PhieuDanhGia::with(['sinhVien.lop.khoa', 'hocKy'])->whereIn('trang_thai', ['approved', 'locked'])->get();
        $pdf = Pdf::loadView('exports.report-pdf', compact('forms'))->setPaper('a4', 'landscape');

        return $pdf->download('bao-cao-diem-ren-luyen.pdf');
    }
}
