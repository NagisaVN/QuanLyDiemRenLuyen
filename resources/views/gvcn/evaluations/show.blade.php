@extends('layouts.gvcn')

@section('page-title', 'Duyệt phiếu GVCN')

@section('content')
<div class="row g-4">
    <div class="col-xl-8">
        <form id="gvcn-score-form" method="POST" action="{{ route('gvcn.evaluations.update', $phieu) }}" class="table-card p-3">
            @csrf
            @method('PUT')
            <h2 class="h5">{{ $phieu->sinhVien->ho_ten }} - {{ $phieu->sinhVien->ma_sinh_vien }}</h2>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light"><tr><th>Tiêu chí</th><th>Tự chấm</th><th>GVCN</th></tr></thead>
                    <tbody>
                        @foreach ($phieu->chiTietDanhGias as $detail)
                            <tr>
                                <td>{{ $detail->tieuChi->ten_tieu_chi }} <span class="text-secondary">/ {{ $detail->tieuChi->diem_toi_da }}</span></td>
                                <td>{{ $detail->diem_tu_cham }}</td>
                                <td><input class="form-control" type="number" min="0" max="{{ $detail->tieuChi->diem_toi_da }}" name="scores[{{ $detail->tieu_chi_id }}]" value="{{ old('scores.'.$detail->tieu_chi_id, $detail->diem_gvcn ?? $detail->diem_tu_cham) }}"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <label class="form-label">Nhận xét GVCN</label>
            <textarea class="form-control mb-3" name="nhan_xet_gvcn" rows="3">{{ old('nhan_xet_gvcn', $phieu->nhan_xet_gvcn) }}</textarea>
        </form>
        <div class="d-flex gap-2 mt-3">
                <button class="btn btn-primary" type="submit" form="gvcn-score-form">Lưu điểm</button>
                <form method="POST" action="{{ route('gvcn.evaluations.confirm', $phieu) }}">
                    @csrf
                    <input type="hidden" name="nhan_xet_gvcn" value="{{ $phieu->nhan_xet_gvcn }}">
                    <button class="btn btn-success" type="submit">Xác nhận GVCN</button>
                </form>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="table-card p-3">
            <h2 class="h5">Minh chứng</h2>
            @forelse ($phieu->minhChungs as $file)
                <div class="border-bottom py-2">
                    <a href="{{ route('minh-chung.download', $file) }}">{{ $file->ten_file }}</a>
                    <form method="POST" action="{{ route('gvcn.evidence.review', $file) }}" class="row g-2 mt-2">
                        @csrf
                        <div class="col-7">
                            <select class="form-select form-select-sm" name="trang_thai">
                                @foreach (['pending' => 'Chờ duyệt', 'approved' => 'Đạt', 'rejected' => 'Không đạt'] as $value => $label)
                                    <option value="{{ $value }}" @selected($file->trang_thai === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-5"><button class="btn btn-sm btn-outline-primary w-100">Lưu</button></div>
                        <div class="col-12"><input class="form-control form-control-sm" name="ghi_chu_duyet" value="{{ $file->ghi_chu_duyet }}" placeholder="Ghi chú"></div>
                    </form>
                </div>
            @empty
                <div class="text-secondary">Chưa có minh chứng.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
