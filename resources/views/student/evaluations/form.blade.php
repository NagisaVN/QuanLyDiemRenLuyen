@extends('layouts.sinh-vien')

@section('page-title', 'Phiếu tự đánh giá')

@section('content')
@php
    $dot = $phieu->dotDanhGia;
    $deadlinePassed = $dot?->ngay_ket_thuc_sinh_vien && now()->greaterThan($dot->ngay_ket_thuc_sinh_vien);
    $deadlineText = $dot?->ngay_ket_thuc_sinh_vien?->format('d/m/Y H:i');
@endphp

@push('styles')
<style>
    .custom-file-item {
        transition: all 0.2s ease;
        background-color: #fff;
    }
    .custom-file-item:hover {
        background-color: #f8f9fa;
        border-color: #adb5bd !important;
    }
</style>
@endpush

<div class="row g-4">
    <div class="col-12">
        <form id="evaluation-form" method="POST" action="{{ route('sinh-vien.evaluations.update') }}" class="table-card p-3 shadow-sm border-0 rounded-4">
            @csrf
            @method('PUT')
            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <h2 class="h5 mb-1">{{ $phieu->hocKy->ten_hoc_ky }}</h2>
                    <div class="text-secondary small">
                        Trạng thái phiếu:
                        <span class="badge text-bg-info">{{ config('ui.statuses.' . $phieu->trang_thai, $phieu->trang_thai) }}</span>
                    </div>
                    @if ($dot)
                        <div class="text-secondary small mt-1">
                            Đợt: {{ $dot->ten_dot }} · Hạn nộp: {{ $deadlineText }}
                        </div>
                    @endif
                </div>
                <a class="btn btn-outline-secondary" href="{{ route('sinh-vien.evaluations.print') }}">
                    <i class="bi bi-printer me-1"></i>In phiếu
                </a>
            </div>

            @if (! $canEdit)
                <div class="alert alert-warning">
                    {{ $deadlinePassed ? 'Đã hết thời hạn nộp phiếu đánh giá.' : 'Phiếu đã được duyệt, đã khóa hoặc không còn trong thời gian chỉnh sửa.' }}
                </div>
            @endif

            @include('evaluations.partials.rubric-table', [
                'rubric' => $rubric,
                'stage' => 'student',
                'canEdit' => $canEdit,
                'showHoiDong' => true,
            ])

            <div class="mb-3">
                <label class="form-label">Nhận xét sinh viên</label>
                <textarea class="form-control" name="nhan_xet_sinh_vien" rows="3" @disabled(! $canEdit)>{{ old('nhan_xet_sinh_vien', $phieu->nhan_xet_sinh_vien) }}</textarea>
            </div>
        </form>
        <div class="d-flex gap-2 flex-wrap mt-3">
            <button class="btn btn-primary" type="submit" form="evaluation-form" @disabled(! $canEdit)>Lưu phiếu</button>
            <form method="POST" action="{{ route('sinh-vien.evaluations.submit') }}">
                @csrf
                <button class="btn btn-success" type="submit" @disabled(! $canEdit)>Nộp phiếu</button>
            </form>
        </div>
    </div>
</div>

<div class="row g-4 mt-2">
    <div class="col-lg-4 col-xl-3">
        <div class="table-card p-3 shadow-sm border-0 rounded-4 h-100">
            <h2 class="h5">Tổng điểm</h2>
            <div class="display-6 text-primary">{{ $phieu->diem_cuoi ?? $phieu->diem_hoi_dong ?? $phieu->diem_gvcn ?? $phieu->diem_tu_cham }}/100</div>
            <div class="text-secondary">Xếp loại: {{ $phieu->xep_loai ?? 'Chưa có' }}</div>
        </div>
    </div>
    <div class="col-lg-8 col-xl-9">
        <div class="table-card p-3 shadow-sm border-0 rounded-4 h-100">
            <h2 class="h5">Minh chứng</h2>
            <form method="POST" action="{{ route('sinh-vien.evaluations.upload') }}" enctype="multipart/form-data" class="vstack gap-3 mb-3">
                @csrf
                <div>
                    <label class="form-label">Gắn với dòng đánh giá</label>
                    <select class="form-select" name="muc_tieu_chi_id" @disabled(! $canEdit)>
                        <option value="">Chung cho phiếu</option>
                        @foreach ($rubric as $section)
                            <optgroup label="{{ $section['criterion']->ten_tieu_chi }}">
                                @foreach ($section['rows'] as $row)
                                    @if ($row['item']->loai === \App\Models\MucTieuChi::TYPE_ITEM)
                                        <option value="{{ $row['item']->id }}">{{ $row['item']->ma_muc }} - {{ $row['item']->ten_muc }}</option>
                                    @endif
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label fw-medium text-secondary small mb-1">Tệp đính kèm (Ảnh/PDF, tối đa 5MB)</label>
                    <input class="form-control form-control-sm" type="file" name="files[]" multiple accept=".jpg,.jpeg,.png,.pdf" @disabled(! $canEdit)>
                </div>
                <textarea class="form-control form-control-sm" name="mo_ta" rows="2" placeholder="Nhập mô tả minh chứng..." @disabled(! $canEdit)></textarea>
                <button class="btn btn-primary btn-sm w-100" type="submit" @disabled(! $canEdit)>
                    <i class="bi bi-cloud-arrow-up me-1"></i> Tải minh chứng lên
                </button>
            </form>
            
            <hr class="my-3 text-secondary opacity-25">
            
            <div class="d-flex flex-column gap-2">
                @forelse ($phieu->minhChungs as $file)
                    <a class="text-decoration-none p-2 border rounded-3 d-flex justify-content-between align-items-center custom-file-item" href="{{ route('minh-chung.download', $file) }}">
                        <div class="d-flex align-items-center gap-2 overflow-hidden">
                            <div class="bg-light rounded p-2 text-secondary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="bi bi-file-earmark-text fs-5"></i>
                            </div>
                            <div class="text-truncate">
                                <div class="text-dark fw-medium text-truncate" style="font-size: 0.9rem;" title="{{ $file->ten_file }}">{{ $file->ten_file }}</div>
                                @if ($file->mucTieuChi)
                                    <div class="text-secondary text-truncate" style="font-size: 0.75rem;" title="{{ $file->mucTieuChi->ma_muc }}">{{ $file->mucTieuChi->ma_muc }}</div>
                                @endif
                            </div>
                        </div>
                        <span class="badge text-bg-secondary ms-2 flex-shrink-0">{{ config('ui.statuses.' . $file->trang_thai, $file->trang_thai) }}</span>
                    </a>
                @empty
                    <div class="text-center text-secondary py-4 border rounded-3 bg-light">
                        <i class="bi bi-inbox fs-3 d-block mb-2 text-secondary opacity-50"></i>
                        <span class="small">Chưa có minh chứng.</span>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
