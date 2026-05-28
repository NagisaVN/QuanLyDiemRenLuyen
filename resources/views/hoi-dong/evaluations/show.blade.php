@extends('layouts.hoi-dong')

@section('page-title', 'Phiếu hội đồng')

@section('content')
<form id="hoi-dong-score-form" method="POST" action="{{ route('hoi-dong.evaluations.update', $phieu) }}" class="table-card p-3">
    @csrf
    @method('PUT')
    <h2 class="h5">{{ $phieu->sinhVien->ho_ten }} - {{ $phieu->sinhVien->ma_sinh_vien }}</h2>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead class="table-light"><tr><th>Tiêu chí</th><th>Tự chấm</th><th>GVCN</th><th>Hội đồng</th></tr></thead>
            <tbody>
                @foreach ($phieu->chiTietDanhGias as $detail)
                    <tr>
                        <td>{{ $detail->tieuChi->ten_tieu_chi }} <span class="text-secondary">/ {{ $detail->tieuChi->diem_toi_da }}</span></td>
                        <td>{{ $detail->diem_tu_cham }}</td>
                        <td>{{ $detail->diem_gvcn }}</td>
                        <td><input class="form-control" type="number" min="0" max="{{ $detail->tieuChi->diem_toi_da }}" name="scores[{{ $detail->tieu_chi_id }}]" value="{{ old('scores.'.$detail->tieu_chi_id, $detail->diem_hoi_dong ?? $detail->diem_gvcn ?? $detail->diem_tu_cham) }}" @disabled($phieu->trang_thai === 'locked')></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <label class="form-label">Nhận xét hội đồng</label>
    <textarea class="form-control mb-3" name="nhan_xet_hoi_dong" rows="3" @disabled($phieu->trang_thai === 'locked')>{{ old('nhan_xet_hoi_dong', $phieu->nhan_xet_hoi_dong) }}</textarea>
</form>
<div class="d-flex gap-2 flex-wrap mt-3">
        <button class="btn btn-primary" type="submit" form="hoi-dong-score-form" @disabled($phieu->trang_thai === 'locked')>Lưu điểm</button>
        <form method="POST" action="{{ route('hoi-dong.evaluations.approve', $phieu) }}">
            @csrf
            <button class="btn btn-success" type="submit" @disabled($phieu->trang_thai === 'locked')>Xác nhận cuối cùng</button>
        </form>
        <form method="POST" action="{{ route('hoi-dong.evaluations.lock', $phieu) }}">
            @csrf
            <button class="btn btn-danger" type="submit" @disabled($phieu->trang_thai === 'locked')>Khóa phiếu</button>
        </form>
</div>
@endsection
