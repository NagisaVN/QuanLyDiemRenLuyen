@extends(auth()->user()->hasRole('admin') ? 'layouts.admin' : 'layouts.hoi-dong')

@section('page-title', $dot->exists ? 'Sửa đợt đánh giá' : 'Tạo đợt đánh giá')

@section('content')
@php
    $datetime = fn ($value) => $value ? $value->copy()->timezone(config('app.display_timezone'))->format('Y-m-d\TH:i') : '';
@endphp

<form method="POST" action="{{ $dot->exists ? route('admin.dot-danh-gia.update', $dot) : route('admin.dot-danh-gia.store') }}" class="table-card p-3">
    @csrf
    @if ($dot->exists)
        @method('PUT')
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="h4 mb-1">{{ $dot->exists ? 'Sửa đợt đánh giá' : 'Tạo đợt đánh giá' }}</h2>
            <div class="text-secondary">Hệ thống tự động mở, đóng và công bố theo lịch bên dưới. Thời gian nhập theo giờ Việt Nam.</div>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('admin.dot-danh-gia.index') }}">Quay lại</a>
    </div>

    <div class="row g-3">
        <div class="col-md-12">
            <label class="form-label">Tên đợt <span class="text-danger">*</span></label>
            <input class="form-control @error('ten_dot') is-invalid @enderror" name="ten_dot" value="{{ old('ten_dot', $dot->ten_dot) }}" required>
            @error('ten_dot')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-6">
            <label class="form-label">Năm học <span class="text-danger">*</span></label>
            <select class="form-select @error('nam_hoc_id') is-invalid @enderror" name="nam_hoc_id" required>
                <option value="">Chọn năm học</option>
                @foreach ($namHocs as $namHoc)
                    <option value="{{ $namHoc->id }}" @selected((int) old('nam_hoc_id', $dot->nam_hoc_id) === $namHoc->id)>{{ $namHoc->ten_nam_hoc }}</option>
                @endforeach
            </select>
            @error('nam_hoc_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Học kỳ <span class="text-danger">*</span></label>
            <select class="form-select @error('hoc_ky_id') is-invalid @enderror" name="hoc_ky_id" required>
                <option value="">Chọn học kỳ</option>
                @foreach ($hocKys as $hocKy)
                    <option value="{{ $hocKy->id }}" data-nam-hoc-id="{{ $hocKy->nam_hoc_id }}" @selected((int) old('hoc_ky_id', $dot->hoc_ky_id) === $hocKy->id)>
                        {{ $hocKy->ten_hoc_ky }}
                    </option>
                @endforeach
            </select>
            @error('hoc_ky_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-6">
            <label class="form-label">Sinh viên bắt đầu <span class="text-danger">*</span></label>
            <input class="form-control @error('ngay_bat_dau_sinh_vien') is-invalid @enderror" type="datetime-local" name="ngay_bat_dau_sinh_vien" value="{{ old('ngay_bat_dau_sinh_vien', $datetime($dot->ngay_bat_dau_sinh_vien)) }}" required>
            @error('ngay_bat_dau_sinh_vien')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Sinh viên kết thúc <span class="text-danger">*</span></label>
            <input class="form-control @error('ngay_ket_thuc_sinh_vien') is-invalid @enderror" type="datetime-local" name="ngay_ket_thuc_sinh_vien" value="{{ old('ngay_ket_thuc_sinh_vien', $datetime($dot->ngay_ket_thuc_sinh_vien)) }}" required>
            @error('ngay_ket_thuc_sinh_vien')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">GVCN bắt đầu <span class="text-danger">*</span></label>
            <input class="form-control @error('ngay_bat_dau_gvcn') is-invalid @enderror" type="datetime-local" name="ngay_bat_dau_gvcn" value="{{ old('ngay_bat_dau_gvcn', $datetime($dot->ngay_bat_dau_gvcn)) }}" required>
            @error('ngay_bat_dau_gvcn')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">GVCN kết thúc <span class="text-danger">*</span></label>
            <input class="form-control @error('ngay_ket_thuc_gvcn') is-invalid @enderror" type="datetime-local" name="ngay_ket_thuc_gvcn" value="{{ old('ngay_ket_thuc_gvcn', $datetime($dot->ngay_ket_thuc_gvcn)) }}" required>
            @error('ngay_ket_thuc_gvcn')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Ngày công bố</label>
            <input class="form-control @error('ngay_cong_bo') is-invalid @enderror" type="datetime-local" name="ngay_cong_bo" value="{{ old('ngay_cong_bo', $datetime($dot->ngay_cong_bo)) }}" required>
            @error('ngay_cong_bo')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <label class="form-label">Mô tả</label>
            <textarea class="form-control @error('mo_ta') is-invalid @enderror" name="mo_ta" rows="3">{{ old('mo_ta', $dot->mo_ta) }}</textarea>
            @error('mo_ta')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-4">
        <a class="btn btn-outline-secondary" href="{{ route('admin.dot-danh-gia.index') }}">Hủy</a>
        <button class="btn btn-primary" type="submit">Lưu</button>
    </div>
</form>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const namHocSelect = document.querySelector('select[name="nam_hoc_id"]');
            const hocKySelect = document.querySelector('select[name="hoc_ky_id"]');

            if (!namHocSelect || !hocKySelect) {
                return;
            }

            const filterHocKyOptions = () => {
                const selectedNamHocId = namHocSelect.value;
                let currentSelectionVisible = false;

                Array.from(hocKySelect.options).forEach((option) => {
                    if (!option.value) {
                        option.hidden = false;
                        option.disabled = false;
                        return;
                    }

                    const visible = selectedNamHocId !== '' && option.dataset.namHocId === selectedNamHocId;

                    option.hidden = !visible;
                    option.disabled = !visible;

                    if (option.selected && visible) {
                        currentSelectionVisible = true;
                    }
                });

                if (!currentSelectionVisible) {
                    hocKySelect.value = '';
                }
            };

            namHocSelect.addEventListener('change', filterHocKyOptions);
            filterHocKyOptions();
        });
    </script>
@endpush
@endsection
