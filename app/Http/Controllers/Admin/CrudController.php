<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Backup;
use App\Models\HoatDong;
use App\Models\HocKy;
use App\Models\Khoa;
use App\Models\Lop;
use App\Models\MinhChung;
use App\Models\MucTieuChi;
use App\Models\NamHoc;
use App\Models\SinhVien;
use App\Models\SystemLog;
use App\Models\ThongBao;
use App\Models\TieuChi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CrudController extends Controller
{
    public function index(string $module)
    {
        $config = $this->config($module);
        $items = $config['model']::query()->latest('id')->paginate(15);

        return view('admin.crud.index', compact('config', 'items', 'module'));
    }

    public function create(string $module)
    {
        $config = $this->config($module);
        $item = new $config['model'];

        return view('admin.crud.form', [
            'config' => $config,
            'item' => $item,
            'module' => $module,
            'options' => $this->options(),
        ]);
    }

    public function store(Request $request, string $module)
    {
        $config = $this->config($module);
        $data = $this->validated($request, $config);
        $roleNames = $this->selectedRoleNames($request);
        $studentData = $this->studentProfileData($request, null, null, $roleNames);

        $item = DB::transaction(function () use ($request, $config, $data, $roleNames, $studentData) {
            if ($config['model'] === User::class) {
                $data['password'] = Hash::make($data['password'] ?? 'password');

                if ($roleNames->contains('sinh_vien') && blank($data['ma_dang_nhap'] ?? null)) {
                    $data['ma_dang_nhap'] = $studentData['ma_sinh_vien'];
                }
            }

            $item = $config['model']::create($data);
            $this->syncExtras($request, $item, $roleNames, $studentData);

            return $item;
        });

        return redirect()->route('admin.crud.index', $module)->with('status', 'Đã tạo dữ liệu.');
    }

    public function show(string $module, int $id)
    {
        $config = $this->config($module);
        $item = $config['model']::findOrFail($id);

        return view('admin.crud.show', compact('config', 'item', 'module'));
    }

    public function edit(string $module, int $id)
    {
        $config = $this->config($module);
        $item = $config['model']::findOrFail($id);

        return view('admin.crud.form', [
            'config' => $config,
            'item' => $item,
            'module' => $module,
            'options' => $this->options(),
        ]);
    }

    public function update(Request $request, string $module, int $id)
    {
        $config = $this->config($module);
        $item = $config['model']::findOrFail($id);
        $data = $this->validated($request, $config, $id);
        $roleNames = $this->selectedRoleNames($request);
        $studentData = $this->studentProfileData($request, $item instanceof User ? $item : null, $item instanceof User ? $item->sinhVien : null, $roleNames);

        DB::transaction(function () use ($request, $config, $item, $data, $roleNames, $studentData) {
            if ($config['model'] === User::class) {
                if (blank($data['password'] ?? null)) {
                    unset($data['password']);
                } else {
                    $data['password'] = Hash::make($data['password']);
                }

                if ($roleNames->contains('sinh_vien') && blank($data['ma_dang_nhap'] ?? null)) {
                    $data['ma_dang_nhap'] = $studentData['ma_sinh_vien'];
                }
            }

            $item->update($data);
            $this->syncExtras($request, $item, $roleNames, $studentData);
        });

        return redirect()->route('admin.crud.index', $module)->with('status', 'Đã cập nhật dữ liệu.');
    }

    public function destroy(string $module, int $id)
    {
        $config = $this->config($module);
        $config['model']::findOrFail($id)->delete();

        return back()->with('status', 'Đã xóa dữ liệu.');
    }

    private function validated(Request $request, array $config, ?int $id = null): array
    {
        $rules = [];
        foreach ($config['fields'] as $name => $field) {
            if (($field['type'] ?? 'text') === 'display' || $name === 'roles') {
                continue;
            }
            $rules[$name] = $field['rules'] ?? ['nullable'];
        }

        if (($config['model'] ?? null) === User::class) {
            $rules['email'] = ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)];
            $rules['ma_dang_nhap'] = ['nullable', 'string', 'max:50', Rule::unique('users', 'ma_dang_nhap')->ignore($id)];
        }

        $data = $request->validate($rules);

        foreach ($config['fields'] as $name => $field) {
            if (($field['type'] ?? null) === 'checkbox') {
                $data[$name] = $request->boolean($name);
            }

            if (($field['type'] ?? null) === 'datetime-local' && filled($data[$name] ?? null)) {
                $data[$name] = str_replace('T', ' ', $data[$name]);
            }
        }

        if (($config['model'] ?? null) === HoatDong::class) {
            $data['dia_diem'] = $data['dia_diem'] ?: '12 Trịnh Đình Thảo, Tân Phú';
            $data['location_radius_meters'] = $data['location_radius_meters'] ?: 100;
        }

        return $data;
    }

    private function selectedRoleNames(Request $request): Collection
    {
        return Role::whereIn('id', $request->input('roles', []))->pluck('name');
    }

    private function studentProfileData(Request $request, ?User $user, ?SinhVien $sinhVien, Collection $roleNames): array
    {
        if (! $roleNames->contains('sinh_vien')) {
            return [];
        }

        $data = $request->validate([
            'student.lop_id' => ['required', 'exists:lops,id'],
            'student.ma_sinh_vien' => [
                'required',
                'string',
                'max:50',
                Rule::unique('sinh_viens', 'ma_sinh_vien')->ignore($sinhVien?->id),
                Rule::unique('users', 'ma_dang_nhap')->ignore($user?->id),
            ],
            'student.ho_ten' => ['nullable', 'string', 'max:255'],
            'student.ngay_sinh' => ['nullable', 'date'],
            'student.gioi_tinh' => ['nullable', 'string', 'max:20'],
            'student.so_dien_thoai' => ['nullable', 'string', 'max:30'],
            'student.dia_chi' => ['nullable', 'string', 'max:255'],
            'student.trang_thai' => ['required', Rule::in(['dang_hoc', 'bao_luu', 'da_tot_nghiep'])],
        ]);

        return $data['student'];
    }

    private function syncExtras(Request $request, mixed $item, ?Collection $roleNames = null, array $studentData = []): void
    {
        if ($item instanceof User) {
            $roleNames ??= $this->selectedRoleNames($request);
            $item->syncRoles($roleNames->all());

            if ($roleNames->contains('sinh_vien')) {
                $studentData = array_filter([
                    ...$studentData,
                    'user_id' => $item->id,
                    'ho_ten' => filled($studentData['ho_ten'] ?? null) ? $studentData['ho_ten'] : $item->name,
                    'trang_thai' => $studentData['trang_thai'] ?? 'dang_hoc',
                ], fn ($value) => $value !== null);

                $profile = SinhVien::withTrashed()->updateOrCreate(
                    ['user_id' => $item->id],
                    $studentData
                );

                if ($profile->trashed()) {
                    $profile->restore();
                }
            }
        }

        if ($item instanceof HoatDong) {
            $item->khoas()->sync($request->input('khoa_ids', []));
        }
    }

    private function config(string $module): array
    {
        $configs = [
            'users' => ['title' => 'Người dùng', 'model' => User::class, 'columns' => ['name', 'email', 'ma_dang_nhap', 'is_active'], 'fields' => [
                'name' => ['label' => 'Họ tên', 'rules' => ['required', 'string', 'max:255']],
                'email' => ['label' => 'Email', 'type' => 'email', 'rules' => ['required', 'email', 'max:255']],
                'ma_dang_nhap' => ['label' => 'Mã đăng nhập', 'rules' => ['nullable', 'string', 'max:50']],
                'password' => ['label' => 'Mật khẩu', 'type' => 'password', 'rules' => ['nullable', 'string', 'min:6']],
                'is_active' => ['label' => 'Đang hoạt động', 'type' => 'checkbox'],
                'roles' => ['label' => 'Vai trò', 'type' => 'roles'],
            ]],
            'roles' => ['title' => 'Vai trò', 'model' => Role::class, 'columns' => ['name', 'guard_name'], 'fields' => [
                'name' => ['label' => 'Tên vai trò', 'type' => 'select', 'options' => 'role_codes', 'rules' => ['required', Rule::in(array_keys(config('ui.roles', [])))]],
                'guard_name' => ['label' => 'Cổng xác thực', 'type' => 'select', 'options' => 'guards', 'rules' => ['required', Rule::in(['web'])]],
            ]],
            'permissions' => ['title' => 'Phân quyền', 'model' => Permission::class, 'columns' => ['name', 'guard_name'], 'fields' => [
                'name' => ['label' => 'Tên quyền', 'rules' => ['required', 'string', 'max:255']],
                'guard_name' => ['label' => 'Cổng xác thực', 'type' => 'select', 'options' => 'guards', 'rules' => ['required', Rule::in(['web'])]],
            ]],
            'khoas' => ['title' => 'Khoa', 'model' => Khoa::class, 'columns' => ['ma_khoa', 'ten_khoa', 'is_active'], 'fields' => $this->basicFields('ma_khoa', 'ten_khoa')],
            'lops' => ['title' => 'Lớp', 'model' => Lop::class, 'columns' => ['ma_lop', 'ten_lop', 'nien_khoa'], 'fields' => [
                'khoa_id' => ['label' => 'Khoa', 'type' => 'select', 'options' => 'khoas', 'rules' => ['required', 'exists:khoas,id']],
                'gvcn_id' => ['label' => 'GVCN/Cố vấn', 'type' => 'select', 'options' => 'gvcn', 'rules' => ['nullable', 'exists:users,id']],
                'ma_lop' => ['label' => 'Mã lớp', 'rules' => ['required', 'string', 'max:50']],
                'ten_lop' => ['label' => 'Tên lớp', 'rules' => ['required', 'string', 'max:255']],
                'nien_khoa' => ['label' => 'Niên khóa', 'rules' => ['nullable', 'string', 'max:50']],
                'is_active' => ['label' => 'Đang hoạt động', 'type' => 'checkbox'],
            ]],
            'sinh-viens' => ['title' => 'Sinh viên', 'model' => SinhVien::class, 'columns' => ['ma_sinh_vien', 'ho_ten', 'trang_thai'], 'fields' => [
                'user_id' => ['label' => 'Tài khoản', 'type' => 'select', 'options' => 'users', 'rules' => ['required', 'exists:users,id']],
                'lop_id' => ['label' => 'Lớp', 'type' => 'select', 'options' => 'lops', 'rules' => ['required', 'exists:lops,id']],
                'ma_sinh_vien' => ['label' => 'Mã sinh viên', 'rules' => ['required', 'string', 'max:50']],
                'ho_ten' => ['label' => 'Họ tên', 'rules' => ['required', 'string', 'max:255']],
                'ngay_sinh' => ['label' => 'Ngày sinh', 'type' => 'date', 'rules' => ['nullable', 'date']],
                'gioi_tinh' => ['label' => 'Giới tính', 'rules' => ['nullable', 'string', 'max:20']],
                'so_dien_thoai' => ['label' => 'Số điện thoại', 'rules' => ['nullable', 'string', 'max:30']],
                'dia_chi' => ['label' => 'Địa chỉ', 'rules' => ['nullable', 'string', 'max:255']],
                'trang_thai' => ['label' => 'Trạng thái', 'type' => 'select', 'options' => 'sinh_vien_statuses', 'rules' => ['required', Rule::in(['dang_hoc', 'bao_luu', 'da_tot_nghiep'])]],
            ]],
            'nam-hocs' => ['title' => 'Năm học', 'model' => NamHoc::class, 'columns' => ['ten_nam_hoc', 'is_active'], 'fields' => [
                'ten_nam_hoc' => ['label' => 'Tên năm học', 'rules' => ['required', 'string', 'max:50']],
                'ngay_bat_dau' => ['label' => 'Ngày bắt đầu', 'type' => 'date', 'rules' => ['nullable', 'date']],
                'ngay_ket_thuc' => ['label' => 'Ngày kết thúc', 'type' => 'date', 'rules' => ['nullable', 'date']],
                'is_active' => ['label' => 'Đang hoạt động', 'type' => 'checkbox'],
            ]],
            'hoc-kys' => ['title' => 'Học kỳ', 'model' => HocKy::class, 'columns' => ['ten_hoc_ky', 'thu_tu', 'is_active'], 'fields' => [
                'nam_hoc_id' => ['label' => 'Năm học', 'type' => 'select', 'options' => 'nam_hocs', 'rules' => ['required', 'exists:nam_hocs,id']],
                'ten_hoc_ky' => ['label' => 'Tên học kỳ', 'rules' => ['required', 'string', 'max:100']],
                'thu_tu' => ['label' => 'Thứ tự', 'type' => 'number', 'rules' => ['required', 'integer', 'between:1,3']],
                'ngay_bat_dau' => ['label' => 'Ngày bắt đầu', 'type' => 'date', 'rules' => ['nullable', 'date']],
                'ngay_ket_thuc' => ['label' => 'Ngày kết thúc', 'type' => 'date', 'rules' => ['nullable', 'date']],
                'han_tu_danh_gia' => ['label' => 'Hạn tự đánh giá', 'type' => 'datetime-local', 'rules' => ['nullable', 'date']],
                'han_gvcn_duyet' => ['label' => 'Hạn GVCN duyệt', 'type' => 'datetime-local', 'rules' => ['nullable', 'date']],
                'han_hoi_dong_duyet' => ['label' => 'Hạn hội đồng duyệt', 'type' => 'datetime-local', 'rules' => ['nullable', 'date']],
                'ngay_cong_bo' => ['label' => 'Ngày công bố', 'type' => 'datetime-local', 'rules' => ['nullable', 'date']],
                'is_active' => ['label' => 'Đang hoạt động', 'type' => 'checkbox'],
            ]],
            'tieu-chis' => ['title' => 'Tiêu chí', 'model' => TieuChi::class, 'columns' => ['ma_tieu_chi', 'ten_tieu_chi', 'diem_toi_da'], 'fields' => [
                'ma_tieu_chi' => ['label' => 'Mã tiêu chí', 'rules' => ['required', 'string', 'max:50']],
                'ten_tieu_chi' => ['label' => 'Tên tiêu chí', 'rules' => ['required', 'string', 'max:255']],
                'mo_ta' => ['label' => 'Mô tả', 'type' => 'textarea', 'rules' => ['nullable', 'string']],
                'diem_toi_da' => ['label' => 'Điểm tối đa', 'type' => 'number', 'rules' => ['required', 'integer', 'between:0,100']],
                'thu_tu' => ['label' => 'Thứ tự', 'type' => 'number', 'rules' => ['required', 'integer']],
                'is_active' => ['label' => 'Đang hoạt động', 'type' => 'checkbox'],
            ]],
            'muc-tieu-chis' => ['title' => 'Mức tiêu chí', 'model' => MucTieuChi::class, 'columns' => ['ten_muc', 'diem_toi_da', 'thu_tu'], 'fields' => [
                'tieu_chi_id' => ['label' => 'Tiêu chí', 'type' => 'select', 'options' => 'tieu_chis', 'rules' => ['required', 'exists:tieu_chis,id']],
                'ma_muc' => ['label' => 'Mã mức', 'rules' => ['nullable', 'string', 'max:50']],
                'loai' => ['label' => 'Loại', 'type' => 'select', 'options' => 'muc_tieu_chi_types', 'rules' => ['required', Rule::in(['heading', 'item'])]],
                'ten_muc' => ['label' => 'Tên mức', 'rules' => ['required', 'string', 'max:255']],
                'mo_ta' => ['label' => 'Mô tả', 'type' => 'textarea', 'rules' => ['nullable', 'string']],
                'diem_toi_da' => ['label' => 'Điểm tối đa', 'type' => 'number', 'rules' => ['nullable', 'integer', 'between:-25,20']],
                'thu_tu' => ['label' => 'Thứ tự', 'type' => 'number', 'rules' => ['required', 'integer']],
                'is_active' => ['label' => 'Đang hoạt động', 'type' => 'checkbox'],
            ]],
            'minh-chungs' => ['title' => 'Minh chứng', 'model' => MinhChung::class, 'columns' => ['ten_file', 'loai_file', 'trang_thai'], 'fields' => [
                'trang_thai' => ['label' => 'Trạng thái', 'type' => 'select', 'options' => 'minh_chung_statuses', 'rules' => ['required', Rule::in(['pending', 'approved', 'rejected'])]],
                'ghi_chu_duyet' => ['label' => 'Ghi chú duyệt', 'type' => 'textarea', 'rules' => ['nullable', 'string']],
            ]],
            'hoat-dongs' => ['title' => 'Hoạt động', 'model' => HoatDong::class, 'columns' => ['ma_hoat_dong', 'ten_hoat_dong', 'trang_thai'], 'fields' => [
                'tieu_chi_id' => ['label' => 'Tiêu chí cộng điểm', 'type' => 'select', 'options' => 'tieu_chis', 'rules' => ['nullable', 'exists:tieu_chis,id']],
                'ma_hoat_dong' => ['label' => 'Mã hoạt động', 'rules' => ['required', 'string', 'max:50']],
                'ten_hoat_dong' => ['label' => 'Tên hoạt động', 'rules' => ['required', 'string', 'max:255']],
                'loai_hoat_dong' => ['label' => 'Loại hoạt động', 'rules' => ['required', 'string', 'max:100']],
                'mo_ta' => ['label' => 'Mô tả', 'type' => 'textarea', 'rules' => ['nullable', 'string']],
                'dia_diem' => ['label' => 'Địa điểm', 'rules' => ['nullable', 'string', 'max:255']],
                'location_lat' => ['label' => 'Latitude GPS', 'type' => 'number', 'step' => '0.0000001', 'rules' => ['nullable', 'numeric', 'between:-90,90']],
                'location_lng' => ['label' => 'Longitude GPS', 'type' => 'number', 'step' => '0.0000001', 'rules' => ['nullable', 'numeric', 'between:-180,180']],
                'location_radius_meters' => ['label' => 'Bán kính GPS (m)', 'type' => 'number', 'rules' => ['nullable', 'integer', 'min:10', 'max:1000']],
                'thoi_gian_bat_dau' => ['label' => 'Bắt đầu', 'type' => 'datetime-local', 'rules' => ['nullable', 'date']],
                'thoi_gian_ket_thuc' => ['label' => 'Kết thúc', 'type' => 'datetime-local', 'rules' => ['nullable', 'date']],
                'so_luong_toi_da' => ['label' => 'Số lượng tối đa', 'type' => 'number', 'rules' => ['nullable', 'integer', 'min:1']],
                'diem_cong' => ['label' => 'Điểm cộng', 'type' => 'number', 'rules' => ['required', 'integer', 'between:-20,20']],
                'trang_thai' => ['label' => 'Trạng thái', 'type' => 'select', 'options' => 'hoat_dong_statuses', 'rules' => ['required', Rule::in(['draft', 'open', 'closed', 'cancelled'])]],
                'auto_cong_diem' => ['label' => 'Tự động cộng điểm', 'type' => 'checkbox'],
                'is_bat_buoc' => ['label' => 'Hoạt động bắt buộc', 'type' => 'checkbox'],
            ]],
            'thong-baos' => ['title' => 'Thông báo', 'model' => ThongBao::class, 'columns' => ['tieu_de', 'loai', 'is_active'], 'fields' => [
                'hoc_ky_id' => ['label' => 'Học kỳ', 'type' => 'select', 'options' => 'hoc_kys', 'rules' => ['nullable', 'exists:hoc_kys,id']],
                'tieu_de' => ['label' => 'Tiêu đề', 'rules' => ['required', 'string', 'max:255']],
                'noi_dung' => ['label' => 'Nội dung', 'type' => 'textarea', 'rules' => ['required', 'string']],
                'loai' => ['label' => 'Loại', 'rules' => ['required', 'string', 'max:50']],
                'doi_tuong' => ['label' => 'Đối tượng', 'rules' => ['nullable', 'string', 'max:100']],
                'published_at' => ['label' => 'Ngày đăng', 'type' => 'datetime-local', 'rules' => ['nullable', 'date']],
                'het_han_at' => ['label' => 'Hết hạn', 'type' => 'datetime-local', 'rules' => ['nullable', 'date']],
                'is_active' => ['label' => 'Đang hoạt động', 'type' => 'checkbox'],
            ]],
            'logs' => ['title' => 'Nhật ký hệ thống', 'model' => SystemLog::class, 'columns' => ['hanh_dong', 'doi_tuong', 'created_at'], 'fields' => []],
            'backups' => ['title' => 'Sao lưu dữ liệu', 'model' => Backup::class, 'columns' => ['file_name', 'status', 'created_at'], 'fields' => []],
        ];

        abort_unless(isset($configs[$module]), 404);

        $config = $configs[$module];
        $fieldLabels = collect($config['fields'])
            ->mapWithKeys(fn (array $field, string $name) => [$name => $field['label'] ?? config("ui.columns.$name", str($name)->replace('_', ' ')->title())])
            ->all();

        $config['column_labels'] = array_replace(config('ui.columns', []), $fieldLabels, $config['column_labels'] ?? []);

        return $config;
    }

    private function basicFields(string $code, string $name): array
    {
        return [
            $code => ['label' => 'Mã', 'rules' => ['required', 'string', 'max:50']],
            $name => ['label' => 'Tên', 'rules' => ['required', 'string', 'max:255']],
            'mo_ta' => ['label' => 'Mô tả', 'type' => 'textarea', 'rules' => ['nullable', 'string']],
            'is_active' => ['label' => 'Đang hoạt động', 'type' => 'checkbox'],
        ];
    }

    private function options(): array
    {
        return [
            'khoas' => Khoa::pluck('ten_khoa', 'id'),
            'lops' => Lop::pluck('ten_lop', 'id'),
            'users' => User::pluck('name', 'id'),
            'gvcn' => User::whereHas('roles', fn ($query) => $query->where('name', 'gvcn'))->pluck('name', 'id'),
            'nam_hocs' => NamHoc::pluck('ten_nam_hoc', 'id'),
            'hoc_kys' => HocKy::pluck('ten_hoc_ky', 'id'),
            'tieu_chis' => TieuChi::pluck('ten_tieu_chi', 'id'),
            'guards' => collect(config('ui.guards', [])),
            'sinh_vien_statuses' => collect(['dang_hoc', 'bao_luu', 'da_tot_nghiep'])->mapWithKeys(fn (string $status) => [$status => config("ui.statuses.$status", $status)]),
            'minh_chung_statuses' => collect(['pending', 'approved', 'rejected'])->mapWithKeys(fn (string $status) => [$status => config("ui.statuses.$status", $status)]),
            'muc_tieu_chi_types' => collect(['heading' => 'Dòng tiêu đề', 'item' => 'Dòng chấm điểm']),
            'hoat_dong_statuses' => collect(['draft', 'open', 'closed', 'cancelled'])->mapWithKeys(fn (string $status) => [$status => config("ui.statuses.$status", $status)]),
            'role_codes' => collect(config('ui.roles', [])),
            'role_names' => Role::pluck('name', 'id'),
            'roles' => Role::pluck('name', 'id')->map(fn (string $name) => config("ui.roles.$name", $name)),
        ];
    }
}
