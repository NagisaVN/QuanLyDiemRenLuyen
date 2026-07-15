@extends('layouts.doan-hoi')

@section('page-title', 'Quản lý hoạt động')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 mb-0">Hoạt động</h2>
    <a class="btn btn-primary" href="{{ route('doan-hoi.activities.create') }}"><i class="bi bi-plus-lg me-1"></i>Tạo hoạt động</a>
</div>
<div class="table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Hoạt động</th><th>Lịch đăng ký</th><th>Thời gian diễn ra</th><th>Đăng ký</th><th>Điểm danh</th><th>Trạng thái</th><th class="text-end">Thao tác</th></tr></thead>
            <tbody>
                @forelse ($activities as $activity)
                    <tr>
                        <td>
                            {{ $activity->ten_hoat_dong }}
                            <div class="small text-secondary">{{ $activity->ma_hoat_dong }}</div>
                            <div class="small text-secondary"><i class="bi bi-geo-alt me-1"></i>{{ $activity->dia_diem ?? '12 Trịnh Đình Thảo, Tân Phú' }}</div>
                            <div class="small text-secondary">GPS: {{ $activity->location_lat ?? 'TODO' }}, {{ $activity->location_lng ?? 'TODO' }} · {{ $activity->location_radius_meters ?? 100 }}m</div>
                        </td>
                        <td>
                            <div>{{ $activity->displayDate($activity->open_registration_at) ?? 'Chưa đặt' }}</div>
                            <div class="small text-secondary">đến {{ $activity->displayDate($activity->close_registration_at) ?? 'Chưa đặt' }}</div>
                        </td>
                        <td>
                            <div>{{ $activity->displayDate($activity->thoi_gian_bat_dau) ?? 'Chưa đặt' }}</div>
                            <div class="small text-secondary">đến {{ $activity->displayDate($activity->thoi_gian_ket_thuc) ?? 'Chưa đặt' }}</div>
                        </td>
                        <td>{{ $activity->dang_ky_hoat_dongs_count }}</td>
                        <td>{{ $activity->diem_danh_hoat_dongs_count }}</td>
                        <td><span class="badge text-bg-info">{{ config('ui.statuses.' . $activity->trang_thai, $activity->trang_thai) }}</span></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('doan-hoi.activities.registrations', $activity) }}"><i class="bi bi-people"></i></a>
                            <a class="btn btn-sm btn-outline-success" href="{{ route('doan-hoi.activities.qr', $activity) }}"><i class="bi bi-qr-code"></i></a>
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('doan-hoi.activities.edit', $activity) }}"><i class="bi bi-pencil"></i></a>
                            @if (! in_array($activity->trang_thai, ['completed', 'cancelled'], true))
                                <form method="POST" action="{{ route('doan-hoi.activities.cancel', $activity) }}" class="d-inline" onsubmit="return confirm('Hủy hoạt động này?')">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-danger" type="submit" title="Hủy hoạt động"><i class="bi bi-x-circle"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-secondary py-4">Chưa có hoạt động.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $activities->links() }}</div>
@endsection
