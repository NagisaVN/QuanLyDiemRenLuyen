@extends('layouts.gvcn')

@section('page-title', 'Phiếu lớp phụ trách')

@section('content')
<div class="table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Sinh viên</th>
                    <th>Lớp</th>
                    <th>Học kỳ</th>
                    <th>Điểm tự chấm</th>
                    <th>Trạng thái</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($forms as $form)
                    <tr>
                        <td>{{ $form->sinhVien->ho_ten }}<div class="small text-secondary">{{ $form->sinhVien->ma_sinh_vien }}</div></td>
                        <td>{{ $form->sinhVien->lop->ten_lop }}</td>
                        <td>{{ $form->hocKy->ten_hoc_ky }}</td>
                        <td>{{ $form->diem_tu_cham }}</td>
                        <td><span class="badge text-bg-info">{{ config('ui.statuses.' . $form->trang_thai, $form->trang_thai) }}</span></td>
                        <td class="text-end"><a class="btn btn-sm btn-primary" href="{{ route('gvcn.evaluations.show', $form) }}">Duyệt</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-secondary py-4">Chưa có phiếu.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $forms->links() }}</div>
@endsection
