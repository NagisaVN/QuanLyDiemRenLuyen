@extends('layouts.hoi-dong')

@section('page-title', 'Xác nhận điểm cuối cùng')

@section('content')
<div class="table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Sinh viên</th>
                    <th>Lớp</th>
                    <th>Khoa</th>
                    <th>Đợt đánh giá</th>
                    <th>GVCN</th>
                    <th>Cuối cùng</th>
                    <th>Trạng thái</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($forms as $form)
                    <tr>
                        <td>{{ $form->sinhVien->ho_ten }}<div class="small text-secondary">{{ $form->sinhVien->ma_sinh_vien }}</div></td>
                        <td>{{ $form->sinhVien->lop->ten_lop }}</td>
                        <td>{{ $form->sinhVien->lop->khoa->ten_khoa }}</td>
                        <td>
                            {{ $form->dotDanhGia?->ten_dot ?? 'Chưa gắn' }}
                            @if ($form->dotDanhGia)
                                <div class="small text-secondary">{{ config('ui.statuses.' . $form->dotDanhGia->trang_thai, $form->dotDanhGia->trang_thai) }}</div>
                            @endif
                        </td>
                        <td>{{ $form->diem_gvcn }}</td>
                        <td>{{ $form->diem_cuoi ?? $form->diem_hoi_dong }}</td>
                        <td><span class="badge text-bg-info">{{ config('ui.statuses.' . $form->trang_thai, $form->trang_thai) }}</span></td>
                        <td class="text-end"><a class="btn btn-sm btn-primary" href="{{ route('hoi-dong.evaluations.show', $form) }}">Xem</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-secondary py-4">Chưa có phiếu chờ xác nhận.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $forms->links() }}</div>
@endsection
