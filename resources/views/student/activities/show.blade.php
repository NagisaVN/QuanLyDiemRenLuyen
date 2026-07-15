@extends('layouts.sinh-vien')

@section('page-title', 'Chi tiết hoạt động')

@section('content')
<div class="mb-3"><a href="{{ route('sinh-vien.activities.index') }}"><i class="fas fa-arrow-left mr-1"></i>Quay lại danh sách</a></div>
<div class="row">
    <div class="col-xl-8">
        @include('student.activities.partials.card', ['activity' => $hoatDong, 'registered' => $registered])
        <div class="table-card p-3 mt-3">
            <h2 class="h5">Thông tin hoạt động</h2>
            <div style="white-space:pre-line">{{ $hoatDong->mo_ta ?: 'Chưa có mô tả.' }}</div>
            <hr>
            <dl class="row mb-0">
                <dt class="col-sm-4">Mở đăng ký</dt><dd class="col-sm-8">{{ $hoatDong->displayDate($hoatDong->open_registration_at) }}</dd>
                <dt class="col-sm-4">Đóng đăng ký</dt><dd class="col-sm-8">{{ $hoatDong->displayDate($hoatDong->close_registration_at) }}</dd>
                <dt class="col-sm-4">Bắt đầu hoạt động</dt><dd class="col-sm-8">{{ $hoatDong->displayDate($hoatDong->thoi_gian_bat_dau) }}</dd>
                <dt class="col-sm-4">Kết thúc hoạt động</dt><dd class="col-sm-8">{{ $hoatDong->displayDate($hoatDong->thoi_gian_ket_thuc) }}</dd>
                <dt class="col-sm-4">Người tổ chức</dt><dd class="col-sm-8">{{ $hoatDong->creator?->name ?? 'Nhà trường' }}</dd>
            </dl>
        </div>
    </div>
</div>
@endsection
