@extends(auth()->user()->hasRole('admin') ? 'layouts.admin' : 'layouts.hoi-dong')

@section('page-title', 'Đợt đánh giá')

@section('content')
@php
    $badgeMap = [
        'draft' => 'text-bg-secondary',
        'open' => 'text-bg-success',
        'closed' => 'text-bg-dark',
        'published' => 'text-bg-primary',
    ];
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="h4 mb-1">Quản lý thời hạn đánh giá</h2>
        <div class="text-secondary">Admin và Công Tác Sinh Viên cấu hình lịch; hệ thống tự động mở, đóng và công bố.</div>
    </div>
    @can('manage_dot_danh_gia')
        <a class="btn btn-primary" href="{{ route('admin.dot-danh-gia.create') }}">
            <i class="fas fa-plus me-1"></i> Tạo đợt
        </a>
    @endcan
</div>

<div class="table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Tên đợt</th>
                    <th>Năm học</th>
                    <th>Học kỳ</th>
                    <th>Hạn sinh viên</th>
                    <th>Hạn GVCN</th>
                    <th>Công bố</th>
                    <th>Trạng thái</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($dots as $dot)
                    @php($effectiveStatus = $dot->effectiveStatus())
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $dot->ten_dot }}</div>
                            <div class="small text-secondary">{{ $dot->creator?->name }}</div>
                        </td>
                        <td>{{ $dot->namHoc?->ten_nam_hoc }}</td>
                        <td>{{ $dot->hocKy?->ten_hoc_ky }}</td>
                        <td>
                            <div>{{ $dot->displayDate($dot->ngay_bat_dau_sinh_vien) }}</div>
                            <div class="small text-secondary">{{ $dot->displayDate($dot->ngay_ket_thuc_sinh_vien) }}</div>
                        </td>
                        <td>
                            <div>{{ $dot->displayDate($dot->ngay_bat_dau_gvcn) }}</div>
                            <div class="small text-secondary">{{ $dot->displayDate($dot->ngay_ket_thuc_gvcn) }}</div>
                        </td>
                        <td>{{ $dot->displayDate($dot->ngay_cong_bo) ?? 'Chưa đặt' }}</td>
                        <td>
                            <span class="badge {{ $badgeMap[$effectiveStatus] ?? 'text-bg-secondary' }}">
                                {{ config('ui.statuses.' . $effectiveStatus, $effectiveStatus) }}
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-1 flex-wrap justify-content-end">
                                @if ($effectiveStatus === 'published')
                                    @can('manage_dot_danh_gia')
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.dot-danh-gia.results', $dot) }}">Xem kết quả</a>
                                    @endcan
                                    @can('export reports')
                                        <a class="btn btn-sm btn-outline-success" href="{{ route('admin.dot-danh-gia.export', $dot) }}">Xuất Excel</a>
                                    @endcan
                                @elseif ($effectiveStatus === 'draft')
                                    @can('manage_dot_danh_gia')
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.dot-danh-gia.edit', $dot) }}">Sửa</a>
                                    @endcan
                                    @can('manage_dot_danh_gia')
                                        <form method="POST" action="{{ route('admin.dot-danh-gia.destroy', $dot) }}" onsubmit="return confirm('Xóa đợt đánh giá này?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" type="submit">Xóa</button>
                                        </form>
                                    @endcan
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-secondary py-4">Chưa có đợt đánh giá.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $dots->links() }}</div>
@endsection
