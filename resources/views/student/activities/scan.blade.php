@extends('layouts.sinh-vien')

@section('page-title', 'Điểm danh hoạt động')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="table-card p-4 text-center">
            <div class="display-6 mb-3"><i class="bi bi-qr-code-scan"></i></div>
            <h2 class="h4">Xác nhận điểm danh</h2>
            <p class="text-secondary">Hệ thống cần vị trí GPS hiện tại để kiểm tra bạn đang ở đúng khu vực hoạt động.</p>

            <div id="attendance-alert" class="alert alert-info text-start">
                Bấm nút bên dưới và cho phép trình duyệt truy cập vị trí.
            </div>

            <button id="scan-attendance" class="btn btn-primary btn-lg">
                <i class="bi bi-geo-alt me-1"></i> Lấy vị trí và điểm danh
            </button>
            <a href="{{ route('sinh-vien.activities.index') }}" class="btn btn-outline-secondary btn-lg ms-2">Về hoạt động</a>
        </div>
    </div>
</div>

@push('scripts')
<script>
const button = document.getElementById('scan-attendance');
const alertBox = document.getElementById('attendance-alert');
const csrf = document.querySelector('meta[name="csrf-token"]').content;

function showResult(type, message) {
    alertBox.className = `alert alert-${type} text-start`;
    alertBox.textContent = message;
}

async function submitAttendance(position) {
    const response = await fetch('{{ route('api.attendance.scan') }}', {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf,
        },
        body: JSON.stringify({
            sessionId: @json($sessionId),
            token: @json($token),
            latitude: position.coords.latitude,
            longitude: position.coords.longitude,
            accuracy: position.coords.accuracy,
        }),
    });

    const data = await response.json();

    if (! response.ok) {
        const firstError = Object.values(data.errors || {})[0]?.[0];
        throw new Error(firstError || data.message || 'Không thể điểm danh.');
    }

    showResult('success', data.message);
}

button.addEventListener('click', () => {
    if (! navigator.geolocation) {
        showResult('danger', 'Trình duyệt không hỗ trợ lấy vị trí GPS.');
        return;
    }

    button.disabled = true;
    showResult('info', 'Đang lấy vị trí GPS...');

    navigator.geolocation.getCurrentPosition(
        async (position) => {
            try {
                await submitAttendance(position);
            } catch (error) {
                showResult('danger', error.message);
            } finally {
                button.disabled = false;
            }
        },
        () => {
            showResult('danger', 'Bạn cần cho phép truy cập vị trí để điểm danh.');
            button.disabled = false;
        },
        {
            enableHighAccuracy: true,
            timeout: 15000,
            maximumAge: 0,
        }
    );
});
</script>
@endpush
@endsection
