@extends('layouts.sinh-vien')

@section('page-title', 'Lịch sử điểm')

@section('content')
<div class="table-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Học kỳ</th>
                    <th>Năm học</th>
                    <th>Điểm</th>
                    <th>Điểm hoạt động</th>
                    <th>Xếp loại</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($histories as $row)
                    <tr>
                        <td>{{ $row->hocKy?->ten_hoc_ky }}</td>
                        <td>{{ $row->hocKy?->namHoc?->ten_nam_hoc }}</td>
                        <td>{{ $row->tong_diem }}</td>
                        <td>{{ $row->diem_hoat_dong }}</td>
                        <td>{{ $row->xep_loai }}</td>
                        <td>{{ config('ui.statuses.' . $row->trang_thai, $row->trang_thai) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-secondary py-4">Chưa có lịch sử điểm.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $histories->links() }}</div>
@endsection
