@extends(auth()->user()->hasRole('admin') ? 'layouts.admin' : 'layouts.hoi-dong')

@section('page-title', 'Kết quả đợt đánh giá')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="h4 mb-1">{{ $dotDanhGia->ten_dot }}</h2>
        <div class="text-secondary">
            {{ $dotDanhGia->hocKy?->ten_hoc_ky }} · {{ $dotDanhGia->namHoc?->ten_nam_hoc }}
        </div>
    </div>
    <div class="d-flex gap-2">
        @can('export reports')
            <a class="btn btn-success" href="{{ route('admin.dot-danh-gia.export', $dotDanhGia) }}">Xuất Excel</a>
        @endcan
        <a class="btn btn-outline-secondary" href="{{ route('admin.dot-danh-gia.index') }}">Quay lại</a>
    </div>
</div>

<div class="table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Mã SV</th>
                    <th>Họ tên</th>
                    <th>Lớp</th>
                    <th>Khoa</th>
                    <th>Tự chấm</th>
                    <th>GVCN</th>
                    <th>Hội đồng</th>
                    <th>Cuối cùng</th>
                    <th>Xếp loại</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($forms as $form)
                    <tr>
                        <td>{{ $form->sinhVien?->ma_sinh_vien }}</td>
                        <td>{{ $form->sinhVien?->ho_ten }}</td>
                        <td>{{ $form->sinhVien?->lop?->ten_lop }}</td>
                        <td>{{ $form->sinhVien?->lop?->khoa?->ten_khoa }}</td>
                        <td>{{ $form->diem_tu_cham ?? '-' }}</td>
                        <td>{{ $form->diem_gvcn ?? '-' }}</td>
                        <td>{{ $form->diem_hoi_dong ?? '-' }}</td>
                        <td>{{ $form->diem_cuoi ?? $form->diemRenLuyen?->tong_diem ?? '-' }}</td>
                        <td>{{ $form->xep_loai ?? $form->diemRenLuyen?->xep_loai ?? '-' }}</td>
                        <td>
                            <span class="badge text-bg-info">{{ config('ui.statuses.' . $form->trang_thai, $form->trang_thai) }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center text-secondary py-4">Chưa có phiếu đánh giá cho đợt này.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $forms->links() }}</div>
@endsection
