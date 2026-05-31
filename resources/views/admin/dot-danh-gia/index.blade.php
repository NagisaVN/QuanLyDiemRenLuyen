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
        <div class="text-secondary">Admin và Công Tác Sinh Viên tạo/mở/đóng/công bố các đợt đánh giá.</div>
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
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $dot->ten_dot }}</div>
                            <div class="small text-secondary">{{ $dot->creator?->name }}</div>
                        </td>
                        <td>{{ $dot->namHoc?->ten_nam_hoc }}</td>
                        <td>{{ $dot->hocKy?->ten_hoc_ky }}</td>
                        <td>
                            <div>{{ $dot->ngay_bat_dau_sinh_vien?->format('d/m/Y H:i') }}</div>
                            <div class="small text-secondary">{{ $dot->ngay_ket_thuc_sinh_vien?->format('d/m/Y H:i') }}</div>
                        </td>
                        <td>
                            <div>{{ $dot->ngay_bat_dau_gvcn?->format('d/m/Y H:i') }}</div>
                            <div class="small text-secondary">{{ $dot->ngay_ket_thuc_gvcn?->format('d/m/Y H:i') }}</div>
                        </td>
                        <td>{{ $dot->ngay_cong_bo?->format('d/m/Y H:i') ?? 'Chưa đặt' }}</td>
                        <td>
                            <span class="badge {{ $badgeMap[$dot->trang_thai] ?? 'text-bg-secondary' }}">
                                {{ config('ui.statuses.' . $dot->trang_thai, $dot->trang_thai) }}
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-1 flex-wrap justify-content-end">
                                @can('manage_dot_danh_gia')
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.dot-danh-gia.edit', $dot) }}">Sửa</a>
                                @endcan
                                @can('open_dot_danh_gia')
                                    <form method="POST" action="{{ route('admin.dot-danh-gia.open', $dot) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-success" type="submit">Mở</button>
                                    </form>
                                @endcan
                                @can('close_dot_danh_gia')
                                    <form method="POST" action="{{ route('admin.dot-danh-gia.close', $dot) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-dark" type="submit">Đóng</button>
                                    </form>
                                @endcan
                                @can('publish_dot_danh_gia')
                                    <form method="POST" action="{{ route('admin.dot-danh-gia.publish', $dot) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-primary" type="submit">Công bố</button>
                                    </form>
                                @endcan
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
