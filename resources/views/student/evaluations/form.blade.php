@extends('layouts.sinh-vien')

@section('page-title', 'Phiếu tự đánh giá')

@section('content')
<div class="row g-4">
    <div class="col-xl-8">
        <form id="evaluation-form" method="POST" action="{{ route('sinh-vien.evaluations.update') }}" class="table-card p-3">
            @csrf
            @method('PUT')
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="h5 mb-0">{{ $phieu->hocKy->ten_hoc_ky }}</h2>
                    <div class="text-secondary small">Trạng thái: <span class="badge text-bg-info">{{ config('ui.statuses.' . $phieu->trang_thai, $phieu->trang_thai) }}</span></div>
                </div>
                <a class="btn btn-outline-secondary" href="{{ route('sinh-vien.evaluations.print') }}"><i class="bi bi-printer me-1"></i>In phiếu</a>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Tiêu chí</th>
                            <th style="width: 140px">Tối đa</th>
                            <th style="width: 170px">Điểm tự chấm</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($phieu->chiTietDanhGias->sortBy('tieuChi.thu_tu') as $detail)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $detail->tieuChi->ten_tieu_chi }}</div>
                                    <div class="small text-secondary">{{ $detail->tieuChi->mo_ta }}</div>
                                </td>
                                <td>{{ $detail->tieuChi->diem_toi_da }}</td>
                                <td>
                                    <input class="form-control" type="number" min="0" max="{{ $detail->tieuChi->diem_toi_da }}" name="scores[{{ $detail->tieu_chi_id }}]" value="{{ old('scores.'.$detail->tieu_chi_id, $detail->diem_tu_cham) }}" @disabled(! $canEdit)>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
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

    <div class="col-xl-4">
        <div class="table-card p-3 mb-4">
            <h2 class="h5">Tổng điểm</h2>
            <div class="display-6 text-primary">{{ $phieu->diem_tu_cham }}/100</div>
            <div class="text-secondary">Xếp loại: {{ $phieu->xep_loai ?? 'Chưa có' }}</div>
        </div>
        <div class="table-card p-3">
            <h2 class="h5">Minh chứng</h2>
            <form method="POST" action="{{ route('sinh-vien.evaluations.upload') }}" enctype="multipart/form-data" class="vstack gap-3 mb-3">
                @csrf
                <div>
                    <label class="form-label">Gắn với tiêu chí</label>
                    <select class="form-select" name="tieu_chi_id" @disabled(! $canEdit)>
                        <option value="">Chung cho phiếu</option>
                        @foreach ($phieu->chiTietDanhGias as $detail)
                            <option value="{{ $detail->tieu_chi_id }}">{{ $detail->tieuChi->ten_tieu_chi }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">File ảnh/PDF, tối đa 5MB/file</label>
                    <input class="form-control" type="file" name="files[]" multiple accept=".jpg,.jpeg,.png,.pdf" @disabled(! $canEdit)>
                </div>
                <textarea class="form-control" name="mo_ta" rows="2" placeholder="Mô tả minh chứng" @disabled(! $canEdit)></textarea>
                <button class="btn btn-outline-primary" type="submit" @disabled(! $canEdit)>Tải minh chứng</button>
            </form>
            <div class="list-group list-group-flush">
                @forelse ($phieu->minhChungs as $file)
                    <a class="list-group-item px-0 d-flex justify-content-between align-items-center" href="{{ route('minh-chung.download', $file) }}">
                        <span><i class="bi bi-paperclip me-1"></i>{{ $file->ten_file }}</span>
                        <span class="badge text-bg-secondary">{{ config('ui.statuses.' . $file->trang_thai, $file->trang_thai) }}</span>
                    </a>
                @empty
                    <div class="text-secondary">Chưa có minh chứng.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
