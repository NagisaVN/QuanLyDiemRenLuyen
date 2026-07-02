@extends('layouts.doan-hoi')

@section('page-title', 'QR điểm danh')

@section('content')
<div class="row g-4">
    <div class="col-xl-5">
        <div class="table-card p-4">
            <h2 class="h5">{{ $hoatDong->ten_hoat_dong }}</h2>
            <div class="small text-secondary mb-3">
                <div><i class="bi bi-geo-alt me-1"></i>{{ $hoatDong->dia_diem ?? '12 Trịnh Đình Thảo, Tân Phú' }}</div>
                <div>GPS: {{ $hoatDong->location_lat ?? 'TODO latitude' }}, {{ $hoatDong->location_lng ?? 'TODO longitude' }} · Bán kính {{ $hoatDong->location_radius_meters ?? 100 }}m</div>
            </div>

            @if ($hoatDong->location_lat === null || $hoatDong->location_lng === null)
                <div class="alert alert-warning">Chưa có tọa độ chính xác. Hãy nhập latitude/longitude từ Google Maps trước khi cho sinh viên quét QR.</div>
            @endif

            <div class="row g-3">
                @foreach (['check_in' => 'Mở điểm danh đầu giờ', 'check_out' => 'Mở điểm danh cuối giờ'] as $type => $label)
                    <div class="col-12">
                        <div class="border rounded-3 p-3">
                            <h3 class="h6">{{ $label }}</h3>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label small">Bắt đầu</label>
                                    <input class="form-control" type="datetime-local" id="{{ $type }}_start" value="{{ now()->format('Y-m-d\TH:i') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small">Kết thúc</label>
                                    <input class="form-control" type="datetime-local" id="{{ $type }}_end" value="{{ now()->addMinutes(30)->format('Y-m-d\TH:i') }}">
                                </div>
                            </div>
                            <button class="btn btn-primary w-100 mt-3 js-open-session" data-type="{{ $type }}">{{ $label }}</button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="text-center mt-4">
                <div id="qrcode" class="d-inline-block p-3 bg-white border rounded-3"></div>
                <div id="qr-url" class="small text-secondary text-break mt-2">Chưa mở phiên điểm danh.</div>
            </div>
        </div>
    </div>

    <div class="col-xl-7">
        <div class="table-card p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center gap-2">
                <h2 class="h5 mb-0">Sinh viên đã điểm danh</h2>
                <button class="btn btn-success" id="approve-attendance">Duyệt cộng điểm</button>
            </div>
            <div class="table-responsive mt-3">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Sinh viên</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Khoảng cách</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($records as $record)
                            <tr>
                                <td>{{ $record->sinhVien?->ho_ten }}<div class="small text-secondary">{{ $record->sinhVien?->ma_sinh_vien }}</div></td>
                                <td>{{ $record->checked_in_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td>{{ $record->check_out_time?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td>
                                    In: {{ $record->check_in_distance_meters ?? '—' }}m<br>
                                    Out: {{ $record->check_out_distance_meters ?? '—' }}m
                                </td>
                                <td>
                                    <span class="badge text-bg-{{ $record->point_awarded ? 'success' : 'info' }}">{{ $record->status }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-secondary py-4">Chưa có lượt điểm danh.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="table-card p-3">
            <h2 class="h5">Phiên điểm danh gần đây</h2>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light"><tr><th>Loại</th><th>Khung giờ</th><th>Trạng thái</th><th>QR</th></tr></thead>
                    <tbody>
                        @forelse ($sessions as $session)
                            @php
                                $url = route('sinh-vien.attendance.scan', ['sessionId' => $session->id, 'token' => $session->token]);
                            @endphp
                            <tr>
                                <td>{{ $session->type }}</td>
                                <td>{{ $session->start_at?->format('d/m H:i') }} - {{ $session->end_at?->format('d/m H:i') }}</td>
                                <td><span class="badge text-bg-{{ $session->is_active ? 'success' : 'secondary' }}">{{ $session->is_active ? 'Đang mở' : 'Đã đóng' }}</span></td>
                                <td><button class="btn btn-sm btn-outline-primary js-show-qr" data-url="{{ $url }}">Hiển thị</button></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-secondary">Chưa có phiên.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <a class="btn btn-outline-secondary mt-3" href="{{ route('doan-hoi.activities.registrations', $hoatDong) }}">Quay lại duyệt đăng ký</a>
    </div>
</div>
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
const csrf = document.querySelector('meta[name="csrf-token"]').content;
const qrBox = document.getElementById('qrcode');
const qrUrl = document.getElementById('qr-url');
let qr;

function renderQr(url) {
    qrBox.innerHTML = '';
    qr = new QRCode(qrBox, { text: url, width: 240, height: 240 });
    qrUrl.textContent = url;
}

document.querySelectorAll('.js-open-session').forEach((button) => {
    button.addEventListener('click', async () => {
        const type = button.dataset.type;
        button.disabled = true;

        try {
            const response = await fetch('{{ route('api.attendance.sessions.store') }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({
                    activityId: {{ $hoatDong->id }},
                    type,
                    startAt: document.getElementById(`${type}_start`).value,
                    endAt: document.getElementById(`${type}_end`).value,
                }),
            });
            const data = await response.json();

            if (! response.ok) {
                throw new Error(data.message || Object.values(data.errors || {})[0]?.[0] || 'Không mở được phiên điểm danh.');
            }

            renderQr(data.checkinUrl);
            alert(data.message);
        } catch (error) {
            alert(error.message);
        } finally {
            button.disabled = false;
        }
    });
});

document.querySelectorAll('.js-show-qr').forEach((button) => {
    button.addEventListener('click', () => renderQr(button.dataset.url));
});

document.getElementById('approve-attendance').addEventListener('click', async () => {
    if (! confirm('Duyệt cộng điểm cho các sinh viên đã đủ check-in và check-out?')) {
        return;
    }

    const response = await fetch('{{ route('api.attendance.approve', $hoatDong) }}', {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrf,
        },
    });
    const data = await response.json();
    alert(data.message || 'Đã xử lý.');

    if (response.ok) {
        window.location.reload();
    }
});
</script>
@endpush
@endsection
