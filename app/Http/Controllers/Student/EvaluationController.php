<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\MinhChung;
use App\Models\PhieuDanhGia;
use App\Services\DiemRenLuyenService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class EvaluationController extends Controller
{
    public function index(Request $request, DiemRenLuyenService $service)
    {
        try {
            $phieu = $service->ensurePhieu($request->user()->sinhVien);
        } catch (ValidationException $exception) {
            return view('student.evaluations.closed', [
                'message' => collect($exception->errors())->flatten()->first() ?: 'Đã hết thời hạn nộp phiếu đánh giá.',
            ]);
        }

        return view('student.evaluations.form', [
            'phieu' => $phieu,
            'canEdit' => $service->canStudentEdit($phieu),
        ]);
    }

    public function update(Request $request, DiemRenLuyenService $service)
    {
        $phieu = $service->ensurePhieu($request->user()->sinhVien);
        $data = $request->validate([
            'scores' => ['required', 'array'],
            'nhan_xet_sinh_vien' => ['nullable', 'string', 'max:2000'],
        ]);

        $service->saveStudentScores($phieu, $data['scores'], $request->user(), $data['nhan_xet_sinh_vien'] ?? null);

        return back()->with('status', 'Đã lưu điểm tự đánh giá.');
    }

    public function submit(Request $request, DiemRenLuyenService $service)
    {
        $service->submit($service->ensurePhieu($request->user()->sinhVien));

        return back()->with('status', 'Đã nộp phiếu tự đánh giá.');
    }

    public function upload(Request $request, DiemRenLuyenService $service)
    {
        $phieu = $service->ensurePhieu($request->user()->sinhVien);

        if (! $service->canStudentEdit($phieu)) {
            throw ValidationException::withMessages(['files' => 'Phiếu đã khóa hoặc đã được duyệt.']);
        }

        $data = $request->validate([
            'tieu_chi_id' => ['nullable', 'exists:tieu_chis,id'],
            'mo_ta' => ['nullable', 'string', 'max:1000'],
            'files' => ['required', 'array', 'max:5'],
            'files.*' => ['file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        if ($phieu->minhChungs()->count() + count($request->file('files', [])) > 5) {
            throw ValidationException::withMessages(['files' => 'Mỗi phiếu chỉ được tải tối đa 5 file.']);
        }

        foreach ($request->file('files', []) as $file) {
            $path = $file->store("minh-chungs/{$phieu->id}");
            MinhChung::create([
                'sinh_vien_id' => $phieu->sinh_vien_id,
                'phieu_danh_gia_id' => $phieu->id,
                'tieu_chi_id' => $data['tieu_chi_id'] ?? null,
                'uploaded_by' => $request->user()->id,
                'ten_file' => $file->getClientOriginalName(),
                'duong_dan' => $path,
                'loai_file' => $file->getClientMimeType(),
                'kich_thuoc' => $file->getSize(),
                'mo_ta' => $data['mo_ta'] ?? null,
            ]);
        }

        return back()->with('status', 'Đã tải minh chứng.');
    }

    public function download(Request $request, MinhChung $minhChung)
    {
        $user = $request->user();
        $owner = $user->sinhVien?->id === $minhChung->sinh_vien_id;
        $gvcn = $user->hasRole('gvcn') && $user->lopPhuTrachs()->whereKey($minhChung->sinhVien->lop_id)->exists();

        abort_unless($owner || $gvcn || $user->hasAnyRole(['admin', 'hoi_dong_khoa']), 403);

        return Storage::download($minhChung->duong_dan, $minhChung->ten_file);
    }

    public function history(Request $request)
    {
        $histories = $request->user()->sinhVien->diemRenLuyens()->with('hocKy.namHoc')->latest()->paginate(10);

        return view('student.evaluations.history', compact('histories'));
    }

    public function print(Request $request, DiemRenLuyenService $service)
    {
        $phieu = $service->ensurePhieu($request->user()->sinhVien);
        $pdf = Pdf::loadView('exports.phieu-pdf', compact('phieu'));

        return $pdf->download("phieu-danh-gia-{$phieu->id}.pdf");
    }
}
