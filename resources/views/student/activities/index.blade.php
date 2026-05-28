@extends('layouts.sinh-vien')

@section('page-title', 'Hoạt động')

@section('content')
<div class="row g-3">
    @forelse ($activities as $activity)
        <div class="col-md-6 col-xl-4">
            <div class="table-card p-3 h-100">
                <div class="d-flex justify-content-between gap-2">
                    <h2 class="h5">{{ $activity->ten_hoat_dong }}</h2>
                    <span class="badge text-bg-primary align-self-start">+{{ $activity->diem_cong }}</span>
                </div>
                <div class="text-secondary small mb-2">{{ $activity->loai_hoat_dong }}</div>
                <p>{{ str($activity->mo_ta)->limit(120) }}</p>
                <div class="small text-secondary mb-3">
                    <div><i class="bi bi-geo-alt me-1"></i>{{ $activity->dia_diem ?? 'Chưa cập nhật' }}</div>
                    <div><i class="bi bi-people me-1"></i>{{ $activity->so_da_dang_ky }}/{{ $activity->so_luong_toi_da ?? '∞' }}</div>
                </div>
                @if (in_array($activity->id, $registeredIds, true))
                    <button class="btn btn-outline-secondary w-100" disabled>Đã đăng ký</button>
                @else
                    <form method="POST" action="{{ route('sinh-vien.activities.register', $activity) }}">
                        @csrf
                        <button class="btn btn-primary w-100" type="submit">Đăng ký</button>
                    </form>
                @endif
            </div>
        </div>
    @empty
        <div class="col-12"><div class="table-card p-4 text-center text-secondary">Chưa có hoạt động đang mở.</div></div>
    @endforelse
</div>
<div class="mt-3">{{ $activities->links() }}</div>
@endsection
