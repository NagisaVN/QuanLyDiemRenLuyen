<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DotDanhGia;
use App\Models\HocKy;
use App\Models\NamHoc;
use App\Services\DotDanhGiaService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DotDanhGiaController extends Controller
{
    public function index()
    {
        $dots = DotDanhGia::query()
            ->with(['namHoc', 'hocKy', 'creator'])
            ->latest('id')
            ->paginate(12);

        return view('admin.dot-danh-gia.index', compact('dots'));
    }

    public function create()
    {
        return view('admin.dot-danh-gia.form', [
            'dot' => new DotDanhGia(['trang_thai' => DotDanhGia::STATUS_DRAFT]),
            ...$this->formOptions(),
        ]);
    }

    public function store(Request $request)
    {
        DotDanhGia::create([
            ...$this->validated($request),
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.dot-danh-gia.index')->with('status', 'Đã tạo đợt đánh giá.');
    }

    public function edit(DotDanhGia $dotDanhGia)
    {
        return view('admin.dot-danh-gia.form', [
            'dot' => $dotDanhGia,
            ...$this->formOptions(),
        ]);
    }

    public function update(Request $request, DotDanhGia $dotDanhGia)
    {
        $dotDanhGia->update([
            ...$this->validated($request),
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.dot-danh-gia.index')->with('status', 'Đã cập nhật đợt đánh giá.');
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

    private function validated(Request $request): array
    {
        return $request->validate([
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
            'ngay_cong_bo' => ['nullable', 'date', 'after_or_equal:ngay_ket_thuc_gvcn'],
            'trang_thai' => ['required', Rule::in([
                DotDanhGia::STATUS_DRAFT,
                DotDanhGia::STATUS_OPEN,
                DotDanhGia::STATUS_CLOSED,
                DotDanhGia::STATUS_PUBLISHED,
            ])],
            'mo_ta' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function formOptions(): array
    {
        return [
            'namHocs' => NamHoc::query()->latest('id')->get(),
            'hocKys' => HocKy::query()->with('namHoc')->orderByDesc('id')->get(),
        ];
    }
}
