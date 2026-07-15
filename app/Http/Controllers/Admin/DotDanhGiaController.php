<?php

namespace App\Http\Controllers\Admin;

use App\Exports\DiemRenLuyenExport;
use App\Http\Controllers\Controller;
use App\Models\DotDanhGia;
use App\Models\HocKy;
use App\Models\NamHoc;
use App\Models\PhieuDanhGia;
use App\Services\AuditLogger;
use App\Services\DotDanhGiaService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class DotDanhGiaController extends Controller
{
    public function index(DotDanhGiaService $service)
    {
        $dots = DotDanhGia::query()
            ->with(['namHoc', 'hocKy', 'creator'])
            ->withCount('phieuDanhGias')
            ->latest('id')
            ->paginate(12);

        return view('admin.dot-danh-gia.index', compact('dots'));
    }

    public function create()
    {
        $options = $this->formOptions();
        $defaultNamHoc = $options['namHocs']->firstWhere('is_active', true) ?? $options['namHocs']->first();
        $defaultHocKy = $options['hocKys']->first(
            fn (HocKy $hocKy) => $hocKy->nam_hoc_id === $defaultNamHoc?->id && $hocKy->is_active
        ) ?? $options['hocKys']->firstWhere('nam_hoc_id', $defaultNamHoc?->id);

        return view('admin.dot-danh-gia.form', [
            'dot' => new DotDanhGia([
                'trang_thai' => DotDanhGia::STATUS_DRAFT,
                'nam_hoc_id' => $defaultNamHoc?->id,
                'hoc_ky_id' => $defaultHocKy?->id,
            ]),
            ...$options,
        ]);
    }

    public function store(Request $request)
    {
        DotDanhGia::create([
            ...$this->validated($request),
            'trang_thai' => DotDanhGia::STATUS_DRAFT,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.dot-danh-gia.index')->with('status', 'Đã tạo đợt đánh giá.');
    }

    public function edit(DotDanhGia $dotDanhGia)
    {
        abort_if($dotDanhGia->effectiveStatus() === DotDanhGia::STATUS_PUBLISHED, 403, 'Không thể sửa đợt đã công bố.');

        return view('admin.dot-danh-gia.form', [
            'dot' => $dotDanhGia,
            ...$this->formOptions(),
        ]);
    }

    public function update(Request $request, DotDanhGia $dotDanhGia)
    {
        abort_if($dotDanhGia->effectiveStatus() === DotDanhGia::STATUS_PUBLISHED, 403, 'Không thể sửa đợt đã công bố.');

        $dotDanhGia->update([
            ...$this->validated($request),
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.dot-danh-gia.index')->with('status', 'Đã cập nhật đợt đánh giá.');
    }

    public function destroy(DotDanhGia $dotDanhGia)
    {
        if ($dotDanhGia->is_system_sample) {
            throw ValidationException::withMessages(['dot_danh_gia' => 'Không thể xóa đợt đánh giá mẫu của hệ thống.']);
        }

        if ($dotDanhGia->effectiveStatus() === DotDanhGia::STATUS_PUBLISHED) {
            throw ValidationException::withMessages(['dot_danh_gia' => 'Không thể xóa đợt đã công bố.']);
        }

        if ($dotDanhGia->phieuDanhGias()->exists()) {
            throw ValidationException::withMessages(['dot_danh_gia' => 'Không thể xóa đợt đã có phiếu đánh giá.']);
        }

        $dotDanhGia->delete();

        return back()->with('status', 'Đã xóa đợt đánh giá.');
    }

    public function open(Request $request, DotDanhGia $dotDanhGia, DotDanhGiaService $service)
    {
        $service->reopen($dotDanhGia, $request->user());

        return back()->with('status', 'Đã mở đợt đánh giá.');
    }

    public function close(Request $request, DotDanhGia $dotDanhGia, DotDanhGiaService $service)
    {
        $service->close($dotDanhGia, $request->user());

        return back()->with('status', 'Đã đóng đợt đánh giá.');
    }

    public function publish(Request $request, DotDanhGia $dotDanhGia, DotDanhGiaService $service)
    {
        $service->publish($dotDanhGia, $request->user());

        return back()->with('status', 'Đã công bố kết quả và khóa phiếu liên quan.');
    }

    public function results(DotDanhGia $dotDanhGia, DotDanhGiaService $service)
    {
        abort_unless($service->getPeriodForViewingResult($dotDanhGia), 404);

        $forms = PhieuDanhGia::query()
            ->with(['sinhVien.lop.khoa', 'hocKy.namHoc', 'dotDanhGia', 'diemRenLuyen'])
            ->where('dot_danh_gia_id', $dotDanhGia->id)
            ->latest('id')
            ->paginate(20);

        return view('admin.dot-danh-gia.results', compact('dotDanhGia', 'forms'));
    }

    public function exportExcel(DotDanhGia $dotDanhGia, DotDanhGiaService $service)
    {
        abort_unless($service->getPeriodForViewingResult($dotDanhGia), 404);
        app(AuditLogger::class)->write('report.exported', $dotDanhGia, ['format' => 'xlsx']);

        return Excel::download(new DiemRenLuyenExport($dotDanhGia), "ket-qua-dot-{$dotDanhGia->id}.xlsx");
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'ten_dot' => ['required', 'string', 'max:255'],
            'nam_hoc_id' => ['required', 'exists:nam_hocs,id'],
            'hoc_ky_id' => [
                'required',
                Rule::exists('hoc_kys', 'id')->where(fn ($query) => $query->where('nam_hoc_id', $request->input('nam_hoc_id'))),
            ],
            'ngay_bat_dau_sinh_vien' => ['required', 'date'],
            'ngay_ket_thuc_sinh_vien' => ['required', 'date', 'after_or_equal:ngay_bat_dau_sinh_vien'],
            'ngay_bat_dau_gvcn' => ['required', 'date', 'after_or_equal:ngay_ket_thuc_sinh_vien'],
            'ngay_ket_thuc_gvcn' => ['required', 'date', 'after_or_equal:ngay_bat_dau_gvcn'],
            'ngay_cong_bo' => ['required', 'date', 'after_or_equal:ngay_ket_thuc_gvcn'],
            'mo_ta' => ['nullable', 'string', 'max:2000'],
        ]);

        foreach (['ngay_bat_dau_sinh_vien', 'ngay_ket_thuc_sinh_vien', 'ngay_bat_dau_gvcn', 'ngay_ket_thuc_gvcn', 'ngay_cong_bo'] as $field) {
            $data[$field] = Carbon::parse($data[$field], config('app.display_timezone'))->utc();
        }

        return $data;
    }

    private function formOptions(): array
    {
        return [
            'namHocs' => NamHoc::query()->latest('id')->get(),
            'hocKys' => HocKy::query()->with('namHoc')->orderByDesc('id')->get(),
        ];
    }
}
