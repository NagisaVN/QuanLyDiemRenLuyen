@extends('layouts.admin')

@section('page-title', ($item->exists ? 'Sửa ' : 'Thêm ') . $config['title'])

@section('content')
<form method="POST" action="{{ $item->exists ? route('admin.crud.update', [$module, $item->id]) : route('admin.crud.store', $module) }}" class="table-card p-3">
    @csrf
    @if ($item->exists)
        @method('PUT')
    @endif

    <div class="row g-3">
        @foreach ($config['fields'] as $name => $field)
            @php
                $type = $field['type'] ?? 'text';
                $raw = old($name, $item->{$name} ?? ($type === 'checkbox' ? true : ''));
                if ($type === 'password') {
                    $raw = old($name, '');
                }
                if ($raw instanceof \Carbon\CarbonInterface) {
                    $raw = $type === 'datetime-local' ? $raw->format('Y-m-d\TH:i') : $raw->format('Y-m-d');
                }
            @endphp
            <div class="{{ $type === 'textarea' ? 'col-12' : 'col-md-6' }}">
                @if ($type === 'checkbox')
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" id="{{ $name }}" name="{{ $name }}" value="1" @checked((bool) $raw)>
                        <label class="form-check-label" for="{{ $name }}">{{ $field['label'] }}</label>
                    </div>
                @elseif ($type === 'roles')
                    <label class="form-label">{{ $field['label'] }}</label>
                    <select class="form-select" name="roles[]" multiple data-role-select>
                        @foreach ($options['roles'] as $id => $label)
                            <option value="{{ $id }}" data-role-name="{{ $options['role_names'][$id] ?? '' }}" @selected($item->exists && $item->roles->contains('id', $id))>{{ $label }}</option>
                        @endforeach
                    </select>
                @elseif ($type === 'select')
                    <label class="form-label" for="{{ $name }}">{{ $field['label'] }}</label>
                    <select class="form-select" id="{{ $name }}" name="{{ $name }}">
                        <option value="">-- Chọn --</option>
                        @foreach (($options[$field['options']] ?? []) as $id => $label)
                            <option value="{{ $id }}" @selected((string) $raw === (string) $id)>{{ $label }}</option>
                        @endforeach
                    </select>
                @elseif ($type === 'textarea')
                    <label class="form-label" for="{{ $name }}">{{ $field['label'] }}</label>
                    <textarea class="form-control" id="{{ $name }}" name="{{ $name }}" rows="4">{{ $raw }}</textarea>
                @elseif ($type === 'password')
                    <label class="form-label" for="{{ $name }}">{{ $field['label'] }}</label>
                    <div class="input-group">
                        <input class="form-control" id="{{ $name }}" name="{{ $name }}" type="password" value="{{ $raw }}" autocomplete="new-password" @if($item->exists) placeholder="Để trống nếu không đổi mật khẩu" @else placeholder="Để trống để dùng mật khẩu mặc định: password" @endif>
                        <button class="btn btn-outline-secondary" type="button" data-toggle-password="{{ $name }}" aria-label="Hiện hoặc ẩn mật khẩu">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="form-text">Mật khẩu sẽ được mã hóa khi lưu. Bấm biểu tượng mắt để kiểm tra mật khẩu vừa nhập.</div>
                @else
                    <label class="form-label" for="{{ $name }}">{{ $field['label'] }}</label>
                    <input class="form-control" id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ $raw }}" @isset($field['step']) step="{{ $field['step'] }}" @endisset @if($type === 'password' && $item->exists) placeholder="Để trống nếu không đổi" @endif>
                @endif
            </div>
        @endforeach

        @if ($module === 'users')
            @php
                $student = $item->exists ? $item->sinhVien : null;
                $selectedRoles = collect(old('roles', $item->exists ? $item->roles->pluck('id')->all() : []))->map(fn ($id) => (string) $id);
                $studentRoleId = collect($options['role_names'] ?? [])->search('sinh_vien');
                $showStudentFields = $student || ($studentRoleId && $selectedRoles->contains((string) $studentRoleId));
            @endphp
            <div class="col-12 {{ $showStudentFields ? '' : 'd-none' }}" data-student-profile>
                <div class="card border mb-0">
                    <div class="card-header bg-light">
                        <h3 class="card-title mb-0">Thông tin sinh viên</h3>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="student_lop_id">Lớp <span class="text-danger">*</span></label>
                                <select class="form-select @error('student.lop_id') is-invalid @enderror" id="student_lop_id" name="student[lop_id]">
                                    <option value="">-- Chọn --</option>
                                    @foreach ($options['lops'] as $id => $label)
                                        <option value="{{ $id }}" @selected((string) old('student.lop_id', $student?->lop_id) === (string) $id)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('student.lop_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="student_ma_sinh_vien">Mã sinh viên <span class="text-danger">*</span></label>
                                <input class="form-control @error('student.ma_sinh_vien') is-invalid @enderror" id="student_ma_sinh_vien" name="student[ma_sinh_vien]" value="{{ old('student.ma_sinh_vien', $student?->ma_sinh_vien) }}">
                                @error('student.ma_sinh_vien')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="student_ho_ten">Họ tên sinh viên</label>
                                <input class="form-control @error('student.ho_ten') is-invalid @enderror" id="student_ho_ten" name="student[ho_ten]" value="{{ old('student.ho_ten', $student?->ho_ten ?? $item->name) }}">
                                @error('student.ho_ten')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="student_trang_thai">Trạng thái <span class="text-danger">*</span></label>
                                <select class="form-select @error('student.trang_thai') is-invalid @enderror" id="student_trang_thai" name="student[trang_thai]">
                                    @foreach ($options['sinh_vien_statuses'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('student.trang_thai', $student?->trang_thai ?? 'dang_hoc') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('student.trang_thai')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="student_ngay_sinh">Ngày sinh</label>
                                <input class="form-control @error('student.ngay_sinh') is-invalid @enderror" id="student_ngay_sinh" name="student[ngay_sinh]" type="date" value="{{ old('student.ngay_sinh', optional($student?->ngay_sinh)->format('Y-m-d')) }}">
                                @error('student.ngay_sinh')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="student_gioi_tinh">Giới tính</label>
                                <input class="form-control @error('student.gioi_tinh') is-invalid @enderror" id="student_gioi_tinh" name="student[gioi_tinh]" value="{{ old('student.gioi_tinh', $student?->gioi_tinh) }}">
                                @error('student.gioi_tinh')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="student_so_dien_thoai">Số điện thoại</label>
                                <input class="form-control @error('student.so_dien_thoai') is-invalid @enderror" id="student_so_dien_thoai" name="student[so_dien_thoai]" value="{{ old('student.so_dien_thoai', $student?->so_dien_thoai) }}">
                                @error('student.so_dien_thoai')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="student_dia_chi">Địa chỉ</label>
                                <input class="form-control @error('student.dia_chi') is-invalid @enderror" id="student_dia_chi" name="student[dia_chi]" value="{{ old('student.dia_chi', $student?->dia_chi) }}">
                                @error('student.dia_chi')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
    <div class="d-flex gap-2 mt-4">
        <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i>Lưu</button>
        <a class="btn btn-outline-secondary" href="{{ route('admin.crud.index', $module) }}">Quay lại</a>
    </div>
</form>
@if ($module === 'users')
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const roleSelect = document.querySelector('[data-role-select]');
                const studentProfile = document.querySelector('[data-student-profile]');

                if (!roleSelect || !studentProfile) {
                    return;
                }

                const toggleStudentProfile = function () {
                    const hasStudentRole = Array.from(roleSelect.selectedOptions)
                        .some((option) => option.dataset.roleName === 'sinh_vien');

                    studentProfile.classList.toggle('d-none', !hasStudentRole);
                };

                roleSelect.addEventListener('change', toggleStudentProfile);
                toggleStudentProfile();

                document.querySelectorAll('[data-toggle-password]').forEach((button) => {
                    button.addEventListener('click', function () {
                        const input = document.getElementById(this.dataset.togglePassword);
                        const icon = this.querySelector('i');

                        if (!input) {
                            return;
                        }

                        input.type = input.type === 'password' ? 'text' : 'password';
                        icon.classList.toggle('bi-eye');
                        icon.classList.toggle('bi-eye-slash');
                    });
                });
            });
        </script>
    @endpush
@endif
@endsection
