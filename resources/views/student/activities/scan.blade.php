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

            <div id="ios-location-help" class="alert alert-light border text-start d-none">
                <strong>Dành cho iPhone/iPad:</strong>
                hãy mở liên kết bằng Safari, vào
                <em>Cài đặt → Quyền riêng tư &amp; Bảo mật → Dịch vụ định vị → Safari Websites</em>,
                chọn <em>Khi dùng ứng dụng</em> và bật <em>Vị trí chính xác</em>.
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
const iosLocationHelp = document.getElementById('ios-location-help');
const csrf = document.querySelector('meta[name="csrf-token"]').content;
const requiredAccuracy = 100;

if (/iPad|iPhone|iPod/.test(navigator.userAgent)) {
    iosLocationHelp.classList.remove('d-none');
}

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

function locationErrorMessage(error) {
    if (error?.code === 1) {
        return 'iPhone chưa cấp quyền vị trí. Hãy mở bằng Safari, cho phép truy cập vị trí và bật Vị trí chính xác trong Cài đặt.';
    }

    if (error?.code === 2) {
        return 'iPhone chưa xác định được vị trí. Hãy bật Dịch vụ định vị, Wi-Fi hoặc dữ liệu di động rồi thử lại ở nơi thoáng hơn.';
    }

    if (error?.code === 3) {
        return 'Quá thời gian lấy GPS. Hãy giữ Safari mở, di chuyển đến nơi thoáng và bấm thử lại.';
    }

    return error?.message || 'Không thể lấy vị trí GPS.';
}

function getAccuratePosition() {
    return new Promise((resolve, reject) => {
        let watchId = null;
        let bestPosition = null;
        let settled = false;

        const finish = (callback, value) => {
            if (settled) {
                return;
            }

            settled = true;
            clearTimeout(overallTimeout);

            if (watchId !== null) {
                navigator.geolocation.clearWatch(watchId);
            }

            callback(value);
        };

        const overallTimeout = setTimeout(() => {
            if (bestPosition) {
                finish(resolve, bestPosition);
                return;
            }

            finish(reject, { code: 3 });
        }, 35000);

        watchId = navigator.geolocation.watchPosition(
            (position) => {
                if (! bestPosition || position.coords.accuracy < bestPosition.coords.accuracy) {
                    bestPosition = position;
                }

                const accuracy = Math.round(position.coords.accuracy);
                showResult('info', `Đã nhận vị trí (sai số khoảng ${accuracy} m), đang xác nhận...`);

                if (position.coords.accuracy <= requiredAccuracy) {
                    finish(resolve, position);
                }
            },
            (error) => {
                if (error.code === 1) {
                    finish(reject, error);
                }
            },
            {
                enableHighAccuracy: true,
                timeout: 30000,
                maximumAge: 0,
            }
        );
    });
}

button.addEventListener('click', async () => {
    if (! window.isSecureContext) {
        showResult('danger', 'iPhone chỉ cho phép lấy vị trí trên kết nối HTTPS. Hãy mở mã QR bằng đường dẫn https:// trong Safari.');
        return;
    }

    if (! navigator.geolocation) {
        showResult('danger', 'Trình duyệt không hỗ trợ lấy vị trí GPS.');
        return;
    }

    button.disabled = true;
    showResult('info', 'Đang lấy vị trí GPS...');

    try {
        const position = await getAccuratePosition();
        await submitAttendance(position);
    } catch (error) {
        showResult('danger', locationErrorMessage(error));
    } finally {
        button.disabled = false;
    }
});
</script>
@endpush
@endsection
