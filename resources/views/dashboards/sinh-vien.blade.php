@extends('layouts.sinh-vien')

@section('page-title', 'Bảng điều khiển sinh viên')

@section('content')
    @include('dashboards.partials-stats')
    <div class="card table-card">
        <div class="card-header">
            <h3 class="card-title mb-0"><i class="fas fa-clipboard-list mr-2"></i>Phiếu hiện tại</h3>
        </div>
        <div class="card-body">
        @if ($phieu)
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="text-secondary small">Trạng thái</div>
                    <span class="badge text-bg-info">{{ config('ui.statuses.' . $phieu->trang_thai, $phieu->trang_thai) }}</span>
                </div>
                <div class="col-md-3">
                    <div class="text-secondary small">Điểm tự chấm</div>
                    <div class="h4 mb-0">{{ $phieu->diem_tu_cham }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-secondary small">Xếp loại</div>
                    <div class="h5 mb-0">{{ $phieu->xep_loai ?? 'Chưa có' }}</div>
                </div>
                <div class="col-md-3">
                    <a class="btn btn-primary w-100" href="{{ route('sinh-vien.evaluations.index') }}">
                        <i class="fas fa-arrow-right mr-1"></i>Mở phiếu
                    </a>
                </div>
            </div>
        @else
            <p class="text-secondary mb-0">Chưa có hồ sơ sinh viên hoặc học kỳ đang mở.</p>
        @endif
        </div>
    </div>
@endsection
