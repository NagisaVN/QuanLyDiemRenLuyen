@extends('layouts.hoi-dong')

@section('page-title', 'Phiếu Công Tác Sinh Viên')

@section('content')
@php
    $isLocked = $phieu->trang_thai === 'locked' || $phieu->dotDanhGia?->trang_thai === 'published';
    $canReview = $canReview ?? ($phieu->canFinalReviewStatus() && ! $isLocked);
@endphp

<form id="hoi-dong-score-form" method="POST" action="{{ route('hoi-dong.evaluations.update', $phieu) }}" class="table-card p-3">
    @csrf
    @method('PUT')
    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
        <div>
            <h2 class="h5 mb-1">{{ $phieu->sinhVien->ho_ten }} - {{ $phieu->sinhVien->ma_sinh_vien }}</h2>
            <div class="text-secondary small">
                {{ $phieu->sinhVien->lop->ten_lop }} · {{ $phieu->hocKy->ten_hoc_ky }}
            </div>
            @if ($phieu->dotDanhGia)
                <div class="text-secondary small mt-1">
                    Đợt: {{ $phieu->dotDanhGia->ten_dot }} · Trạng thái đợt:
                    {{ config('ui.statuses.' . $phieu->dotDanhGia->trang_thai, $phieu->dotDanhGia->trang_thai) }}
                </div>
            @endif
        </div>
        <span class="badge text-bg-info">{{ config('ui.statuses.' . $phieu->trang_thai, $phieu->trang_thai) }}</span>
    </div>

    @if (! $canReview)
        <div class="alert alert-warning">
            {{ $isLocked ? 'Phiếu đã khóa hoặc đợt đánh giá đã công bố, không thể chỉnh sửa.' : 'Chỉ có thể xác nhận cuối phiếu đã được GVCN duyệt và còn trong thời gian duyệt.' }}
        </div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="border rounded-2 p-2">
                <div class="text-secondary small">Tổng tự chấm</div>
                <div class="h5 mb-0">{{ $phieu->diem_tu_cham }}/100</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="border rounded-2 p-2">
                <div class="text-secondary small">Tổng GVCN</div>
                <div class="h5 mb-0">{{ $phieu->diem_gvcn ?? $phieu->diem_tu_cham }}/100</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="border rounded-2 p-2">
                <div class="text-secondary small">Tổng Công Tác Sinh Viên</div>
                <div class="h5 mb-0">{{ $phieu->diem_hoi_dong ?? $phieu->diem_gvcn ?? $phieu->diem_tu_cham }}/100</div>
            </div>
        </div>
    </div>

    @include('evaluations.partials.rubric-table', [
        'rubric' => $rubric,
        'stage' => 'hoi_dong',
        'canEdit' => $canReview,
        'showHoiDong' => true,
    ])

    <label class="form-label">Nhận xét Công Tác Sinh Viên</label>
    <textarea class="form-control mb-3" name="nhan_xet_hoi_dong" rows="3" @disabled(! $canReview)>{{ old('nhan_xet_hoi_dong', $phieu->nhan_xet_hoi_dong) }}</textarea>
</form>
<div class="d-flex gap-2 flex-wrap mt-3">
    <button class="btn btn-primary" type="submit" form="hoi-dong-score-form" @disabled(! $canReview)>Lưu điểm</button>
    <button class="btn btn-success" type="submit" form="hoi-dong-score-form" formaction="{{ route('hoi-dong.evaluations.approve', $phieu) }}" formmethod="POST" @disabled(! $canReview)>Xác nhận cuối cùng</button>
    <form method="POST" action="{{ route('hoi-dong.evaluations.lock', $phieu) }}">
        @csrf
        <button class="btn btn-danger" type="submit" @disabled($isLocked)>Khóa phiếu</button>
    </form>
</div>
@endsection
