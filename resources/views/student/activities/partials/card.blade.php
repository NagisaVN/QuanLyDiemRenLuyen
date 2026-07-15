@php
    $effectiveStatus = $activity->effectiveStatus();
    $statusLabels = [
        'scheduled' => 'Chưa mở đăng ký',
        'open' => 'Đang mở đăng ký',
        'registration_closed' => 'Đã đóng đăng ký',
        'completed' => 'Đã kết thúc',
    ];
    $statusClasses = ['scheduled' => 'warning', 'open' => 'success', 'registration_closed' => 'secondary', 'completed' => 'dark'];
    $registeredCount = (int) $activity->so_da_dang_ky;
    $remaining = $activity->so_luong_toi_da === null ? null : max(0, $activity->so_luong_toi_da - $registeredCount);
@endphp
<article class="table-card p-3 h-100"
    data-activity-card
    data-activity-id="{{ $activity->id }}"
    data-status="{{ $effectiveStatus }}"
    data-open-at="{{ $activity->open_registration_at?->toIso8601String() }}"
    data-close-at="{{ $activity->close_registration_at?->toIso8601String() }}"
    data-end-at="{{ $activity->thoi_gian_ket_thuc?->toIso8601String() }}"
    data-registered="{{ $registered ? '1' : '0' }}"
    data-capacity="{{ $activity->so_luong_toi_da }}">
    <div class="d-flex justify-content-between gap-2">
        <h2 class="h5"><a href="{{ route('sinh-vien.activities.show', $activity) }}">{{ $activity->ten_hoat_dong }}</a></h2>
        <span class="badge text-bg-primary align-self-start">+{{ $activity->diem_cong }}</span>
    </div>
    <div class="d-flex flex-wrap gap-2 mb-2">
        <span class="badge text-bg-{{ $statusClasses[$effectiveStatus] ?? 'secondary' }}" data-activity-status>{{ $statusLabels[$effectiveStatus] ?? $effectiveStatus }}</span>
        <span class="text-secondary small">{{ $activity->loai_hoat_dong }}</span>
    </div>
    <p>{{ str($activity->mo_ta)->limit(120) }}</p>
    <div class="small text-secondary mb-2">
        <div><i class="bi bi-geo-alt me-1"></i>{{ $activity->dia_diem ?? 'Chưa cập nhật' }}</div>
        <div><i class="bi bi-calendar-event me-1"></i>{{ $activity->displayDate($activity->thoi_gian_bat_dau) }}</div>
        <div><i class="bi bi-people me-1"></i>Đã đăng ký: <strong data-registered-count>{{ $registeredCount }}</strong>/{{ $activity->so_luong_toi_da ?? '∞' }} sinh viên</div>
        @if ($remaining !== null)
            <div>Còn lại: <strong data-remaining-slots>{{ $remaining }}</strong> chỗ</div>
        @endif
    </div>
    <div class="alert alert-light border py-2 small" data-activity-countdown></div>
    <div class="alert alert-danger py-2 small d-none" data-registration-error></div>

    @if ($registered)
        <button class="btn btn-outline-success w-100" disabled data-register-button>Đã đăng ký</button>
    @else
        <form method="POST" action="{{ route('sinh-vien.activities.register', $activity) }}" data-activity-registration-form>
            @csrf
            <button class="btn btn-primary w-100" type="submit" data-register-button @disabled($effectiveStatus !== 'open' || $remaining === 0)>Đăng ký tham gia</button>
        </form>
    @endif
</article>
