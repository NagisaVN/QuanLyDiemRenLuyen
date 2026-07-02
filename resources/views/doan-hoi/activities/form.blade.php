@extends('layouts.doan-hoi')

@section('page-title', $hoatDong->exists ? 'Sửa hoạt động' : 'Tạo hoạt động')

@section('content')
<form method="POST" action="{{ $hoatDong->exists ? route('doan-hoi.activities.update', $hoatDong) : route('doan-hoi.activities.store') }}" class="table-card p-3">
    @csrf
    @if ($hoatDong->exists)
        @method('PUT')
    @endif
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label">Mã hoạt động</label><input class="form-control" name="ma_hoat_dong" value="{{ old('ma_hoat_dong', $hoatDong->ma_hoat_dong) }}"></div>
        <div class="col-md-8"><label class="form-label">Tên hoạt động</label><input class="form-control" name="ten_hoat_dong" value="{{ old('ten_hoat_dong', $hoatDong->ten_hoat_dong) }}"></div>
        <div class="col-md-6">
            <label class="form-label">Loại hoạt động</label>
            <select class="form-select" name="loai_hoat_dong">
                @foreach ($types as $type)
                    <option value="{{ $type }}" @selected(old('loai_hoat_dong', $hoatDong->loai_hoat_dong) === $type)>{{ $type }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Tiêu chí cộng điểm</label>
            <select class="form-select" name="tieu_chi_id">
                <option value="">Không gắn tiêu chí</option>
                @foreach ($tieuChis as $tieuChi)
                    <option value="{{ $tieuChi->id }}" @selected(old('tieu_chi_id', $hoatDong->tieu_chi_id) == $tieuChi->id)>{{ $tieuChi->ten_tieu_chi }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12"><label class="form-label">Mô tả</label><textarea class="form-control" name="mo_ta" rows="3">{{ old('mo_ta', $hoatDong->mo_ta) }}</textarea></div>
        <div class="col-md-6"><label class="form-label">Địa điểm</label><input class="form-control" name="dia_diem" value="{{ old('dia_diem', $hoatDong->dia_diem ?: '12 Trịnh Đình Thảo, Tân Phú') }}"></div>
        <div class="col-md-2"><label class="form-label">Latitude</label><input class="form-control" type="number" step="0.0000001" name="location_lat" value="{{ old('location_lat', $hoatDong->location_lat) }}"></div>
        <div class="col-md-2"><label class="form-label">Longitude</label><input class="form-control" type="number" step="0.0000001" name="location_lng" value="{{ old('location_lng', $hoatDong->location_lng) }}"></div>
        <div class="col-md-2"><label class="form-label">Bán kính GPS (m)</label><input class="form-control" type="number" min="10" max="1000" name="location_radius_meters" value="{{ old('location_radius_meters', $hoatDong->location_radius_meters ?: 100) }}"></div>
        <div class="col-12 small text-secondary">TODO: nhập latitude/longitude chính xác của địa điểm từ Google Maps. Backend chỉ kiểm tra bằng tọa độ và bán kính, không kiểm tra bằng text địa chỉ.</div>
        <div class="col-md-3"><label class="form-label">Bắt đầu</label><input class="form-control" type="datetime-local" name="thoi_gian_bat_dau" value="{{ old('thoi_gian_bat_dau', optional($hoatDong->thoi_gian_bat_dau)->format('Y-m-d\TH:i')) }}"></div>
        <div class="col-md-3"><label class="form-label">Kết thúc</label><input class="form-control" type="datetime-local" name="thoi_gian_ket_thuc" value="{{ old('thoi_gian_ket_thuc', optional($hoatDong->thoi_gian_ket_thuc)->format('Y-m-d\TH:i')) }}"></div>
        <div class="col-md-3"><label class="form-label">Số lượng tối đa</label><input class="form-control" type="number" name="so_luong_toi_da" value="{{ old('so_luong_toi_da', $hoatDong->so_luong_toi_da) }}"></div>
        <div class="col-md-3"><label class="form-label">Điểm cộng/trừ</label><input class="form-control" type="number" name="diem_cong" value="{{ old('diem_cong', $hoatDong->diem_cong ?? 0) }}"></div>
        <div class="col-md-3">
            <label class="form-label">Trạng thái</label>
            <select class="form-select" name="trang_thai">
                @foreach (['draft', 'open', 'closed', 'cancelled'] as $status)
                    <option value="{{ $status }}" @selected(old('trang_thai', $hoatDong->trang_thai ?? 'open') === $status)>{{ config("ui.statuses.$status", $status) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" name="auto_cong_diem" value="1" @checked(old('auto_cong_diem', $hoatDong->auto_cong_diem ?? true))><label class="form-check-label">Tự động cộng điểm</label></div></div>
        <div class="col-md-3 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_bat_buoc" value="1" @checked(old('is_bat_buoc', $hoatDong->is_bat_buoc))><label class="form-check-label">Bắt buộc</label></div></div>
        <div class="col-12">
            <label class="form-label">Áp dụng cho khoa</label>
            <div class="row g-2">
                @foreach ($khoas as $khoa)
                    <div class="col-md-4">
                        <label class="border rounded-3 p-2 d-block">
                            <input type="checkbox" name="khoa_ids[]" value="{{ $khoa->id }}" @checked($hoatDong->exists && $hoatDong->khoas->contains($khoa->id))>
                            {{ $khoa->ten_khoa }}
                        </label>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary" type="submit">Lưu</button>
        <a class="btn btn-outline-secondary" href="{{ route('doan-hoi.activities.index') }}">Quay lại</a>
    </div>
</form>
@endsection
