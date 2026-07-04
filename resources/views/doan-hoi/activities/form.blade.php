@extends('layouts.doan-hoi')

@section('page-title', $hoatDong->exists ? 'Sửa hoạt động' : 'Tạo hoạt động')

@section('content')
@php
    $defaultAddress = '12 Trịnh Đình Thảo, Tân Phú';
    $googleMapsBrowserKey = $googleMapsBrowserKey ?? null;
    $defaultMapCenter = $defaultMapCenter ?? ['lat' => 10.7749241, 'lng' => 106.6345254];
    $locationLatValue = old('location_lat', $hoatDong->location_lat);
    $locationLngValue = old('location_lng', $hoatDong->location_lng);
    $locationRadiusValue = old('location_radius_meters', $hoatDong->location_radius_meters ?: 100);
    $selectedLat = is_numeric($locationLatValue) ? (float) $locationLatValue : null;
    $selectedLng = is_numeric($locationLngValue) ? (float) $locationLngValue : null;
    $hasSelectedLocation = $selectedLat !== null
        && $selectedLng !== null
        && $selectedLat >= -90
        && $selectedLat <= 90
        && $selectedLng >= -180
        && $selectedLng <= 180;
    $mapCenterLat = $hasSelectedLocation ? $selectedLat : (float) $defaultMapCenter['lat'];
    $mapCenterLng = $hasSelectedLocation ? $selectedLng : (float) $defaultMapCenter['lng'];
    $mapRadius = is_numeric($locationRadiusValue)
        ? min(1000, max(10, (int) $locationRadiusValue))
        : 100;
    $mapConfig = [
        'center' => ['lat' => $mapCenterLat, 'lng' => $mapCenterLng],
        'radius' => $mapRadius,
        'hasSelectedLocation' => $hasSelectedLocation,
    ];
@endphp

<form method="POST" action="{{ $hoatDong->exists ? route('doan-hoi.activities.update', $hoatDong) : route('doan-hoi.activities.store') }}" class="table-card p-3">
    @csrf
    @if ($hoatDong->exists)
        @method('PUT')
    @endif

    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label" for="ma_hoat_dong">Mã hoạt động <span class="required-marker" aria-hidden="true">*</span></label>
            <input class="form-control" id="ma_hoat_dong" name="ma_hoat_dong" maxlength="50" required value="{{ old('ma_hoat_dong', $hoatDong->ma_hoat_dong) }}">
        </div>

        <div class="col-md-8">
            <label class="form-label" for="ten_hoat_dong">Tên hoạt động <span class="required-marker" aria-hidden="true">*</span></label>
            <input class="form-control" id="ten_hoat_dong" name="ten_hoat_dong" maxlength="255" required value="{{ old('ten_hoat_dong', $hoatDong->ten_hoat_dong) }}">
        </div>

        <div class="col-md-6">
            <label class="form-label" for="loai_hoat_dong">Loại hoạt động <span class="required-marker" aria-hidden="true">*</span></label>
            <select class="form-select" id="loai_hoat_dong" name="loai_hoat_dong" required>
                @foreach ($types as $type)
                    <option value="{{ $type }}" @selected(old('loai_hoat_dong', $hoatDong->loai_hoat_dong) === $type)>{{ $type }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label" for="tieu_chi_id">Tiêu chí cộng điểm</label>
            <select class="form-select" id="tieu_chi_id" name="tieu_chi_id">
                <option value="">Không gắn tiêu chí</option>
                @foreach ($tieuChis as $tieuChi)
                    <option value="{{ $tieuChi->id }}" @selected(old('tieu_chi_id', $hoatDong->tieu_chi_id) == $tieuChi->id)>{{ $tieuChi->ten_tieu_chi }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-12">
            <label class="form-label" for="mo_ta">Mô tả</label>
            <textarea class="form-control" id="mo_ta" name="mo_ta" rows="3">{{ old('mo_ta', $hoatDong->mo_ta) }}</textarea>
        </div>

        <div class="col-md-6">
            <label class="form-label" for="dia_diem">Địa điểm</label>
            <input class="form-control" id="dia_diem" name="dia_diem" maxlength="255" aria-describedby="dia_diem_hint" value="{{ old('dia_diem', $hoatDong->dia_diem ?: $defaultAddress) }}">
            <div class="form-text text-secondary" id="dia_diem_hint">Tên địa điểm giúp sinh viên nhận biết nơi diễn ra hoạt động.</div>
        </div>

        <div class="col-md-2">
            <label class="form-label" for="location_lat">Latitude</label>
            <input class="form-control" id="location_lat" type="number" step="0.0000001" min="-90" max="90" name="location_lat" aria-describedby="location_lat_hint" value="{{ $locationLatValue }}">
            <div class="form-text text-secondary" id="location_lat_hint">Tự điền khi chọn điểm trên bản đồ.</div>
        </div>

        <div class="col-md-2">
            <label class="form-label" for="location_lng">Longitude</label>
            <input class="form-control" id="location_lng" type="number" step="0.0000001" min="-180" max="180" name="location_lng" aria-describedby="location_lng_hint" value="{{ $locationLngValue }}">
            <div class="form-text text-secondary" id="location_lng_hint">Tự điền khi chọn điểm trên bản đồ.</div>
        </div>

        <div class="col-md-2">
            <label class="form-label" for="location_radius_meters">Bán kính GPS (m)</label>
            <input class="form-control" id="location_radius_meters" type="number" step="1" min="10" max="1000" name="location_radius_meters" aria-describedby="location_radius_hint" value="{{ $locationRadiusValue }}">
            <div class="form-text text-secondary" id="location_radius_hint">Sinh viên cần ở trong bán kính này để điểm danh.</div>
        </div>

        <div class="col-12">
            @if ($googleMapsBrowserKey)
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-2 mb-2">
                    <div>
                        <label class="form-label mb-1">Chọn điểm trên Google Maps</label>
                        <div class="small text-secondary">Click vào bản đồ hoặc kéo ghim để cập nhật latitude/longitude. Vòng tròn thể hiện bán kính GPS.</div>
                    </div>
                    <button class="btn btn-outline-primary btn-sm" type="button" id="use-current-location">
                        <i class="bi bi-crosshair mr-1"></i>Dùng vị trí hiện tại
                    </button>
                </div>
                <div id="activity-location-map" class="activity-location-map" role="application" aria-label="Bản đồ chọn tọa độ hoạt động"></div>
                <div id="location-map-status" class="small text-secondary mt-2">
                    {{ $hasSelectedLocation ? 'Đang hiển thị tọa độ đã lưu.' : 'Chưa chọn tọa độ. Bản đồ đang mở gần địa điểm mặc định.' }}
                </div>
            @else
                <div class="alert alert-warning mb-0">
                    Chưa cấu hình Google Maps. Thêm <code>GOOGLE_MAPS_BROWSER_KEY</code> vào môi trường để bật chọn điểm trên bản đồ; hiện tại vẫn có thể nhập latitude/longitude thủ công.
                </div>
            @endif
        </div>

        <div class="col-md-3">
            <label class="form-label" for="thoi_gian_bat_dau">Bắt đầu</label>
            <input class="form-control" id="thoi_gian_bat_dau" type="datetime-local" name="thoi_gian_bat_dau" value="{{ old('thoi_gian_bat_dau', optional($hoatDong->thoi_gian_bat_dau)->format('Y-m-d\TH:i')) }}">
        </div>

        <div class="col-md-3">
            <label class="form-label" for="thoi_gian_ket_thuc">Kết thúc</label>
            <input class="form-control" id="thoi_gian_ket_thuc" type="datetime-local" name="thoi_gian_ket_thuc" value="{{ old('thoi_gian_ket_thuc', optional($hoatDong->thoi_gian_ket_thuc)->format('Y-m-d\TH:i')) }}">
        </div>

        <div class="col-md-3">
            <label class="form-label" for="so_luong_toi_da">Số lượng tối đa</label>
            <input class="form-control" id="so_luong_toi_da" type="number" step="1" min="1" name="so_luong_toi_da" value="{{ old('so_luong_toi_da', $hoatDong->so_luong_toi_da) }}">
        </div>

        <div class="col-md-3">
            <label class="form-label" for="diem_cong">Điểm cộng/trừ <span class="required-marker" aria-hidden="true">*</span></label>
            <input class="form-control" id="diem_cong" type="number" step="1" min="-20" max="20" name="diem_cong" required value="{{ old('diem_cong', $hoatDong->diem_cong ?? 0) }}">
        </div>

        <div class="col-md-3">
            <label class="form-label" for="trang_thai">Trạng thái <span class="required-marker" aria-hidden="true">*</span></label>
            <select class="form-select" id="trang_thai" name="trang_thai" required>
                @foreach (['draft', 'open', 'closed', 'cancelled'] as $status)
                    <option value="{{ $status }}" @selected(old('trang_thai', $hoatDong->trang_thai ?? 'open') === $status)>{{ config("ui.statuses.$status", $status) }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3 d-flex align-items-end">
            <div class="form-check">
                <input class="form-check-input" id="auto_cong_diem" type="checkbox" name="auto_cong_diem" value="1" @checked(old('auto_cong_diem', $hoatDong->auto_cong_diem ?? true))>
                <label class="form-check-label" for="auto_cong_diem">Tự động cộng điểm</label>
            </div>
        </div>

        <div class="col-md-3 d-flex align-items-end">
            <div class="form-check">
                <input class="form-check-input" id="is_bat_buoc" type="checkbox" name="is_bat_buoc" value="1" @checked(old('is_bat_buoc', $hoatDong->is_bat_buoc))>
                <label class="form-check-label" for="is_bat_buoc">Bắt buộc</label>
            </div>
        </div>

        <div class="col-12">
            <label class="form-label">Áp dụng cho khoa</label>
            <div class="row g-2">
                @foreach ($khoas as $khoa)
                    <div class="col-md-4">
                        <label class="border rounded-3 p-2 d-block">
                            <input type="checkbox" name="khoa_ids[]" value="{{ $khoa->id }}" @checked($hoatDong->exists && $hoatDong->khoas->contains($khoa->id))>
                            {{ $khoa->ten_khoa }}
                        </label>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary" type="submit">Lưu</button>
        <a class="btn btn-outline-secondary" href="{{ route('doan-hoi.activities.index') }}">Quay lại</a>
    </div>
</form>

@push('styles')
<style>
    .required-marker {
        color: #dc3545;
        font-weight: 700;
    }

    .activity-location-map {
        width: 100%;
        min-height: 360px;
        border: 1px solid #ced4da;
        border-radius: 6px;
        overflow: hidden;
        background: #f8fafc;
    }

    @media (max-width: 575.98px) {
        .activity-location-map {
            min-height: 300px;
        }
    }
</style>
@endpush

@if ($googleMapsBrowserKey)
    @push('scripts')
    <script>
        (function () {
            const config = @json($mapConfig);

            let map;
            let marker;
            let circle;

            const mapElement = document.getElementById('activity-location-map');
            const statusElement = document.getElementById('location-map-status');
            const currentLocationButton = document.getElementById('use-current-location');
            const latInput = document.getElementById('location_lat');
            const lngInput = document.getElementById('location_lng');
            const radiusInput = document.getElementById('location_radius_meters');

            function setStatus(message, type = 'secondary') {
                if (! statusElement) {
                    return;
                }

                statusElement.className = `small text-${type} mt-2`;
                statusElement.textContent = message;
            }

            function readRadius() {
                const value = Number(radiusInput?.value);

                if (! Number.isFinite(value)) {
                    return 100;
                }

                return Math.min(1000, Math.max(10, Math.round(value)));
            }

            function normalizePosition(position) {
                const lat = typeof position.lat === 'function' ? position.lat() : Number(position.lat);
                const lng = typeof position.lng === 'function' ? position.lng() : Number(position.lng);

                if (! Number.isFinite(lat) || ! Number.isFinite(lng) || lat < -90 || lat > 90 || lng < -180 || lng > 180) {
                    return null;
                }

                return { lat, lng };
            }

            function setLocation(position, options = {}) {
                const normalizedPosition = normalizePosition(position);

                if (! normalizedPosition || ! map || ! circle) {
                    return;
                }

                latInput.value = normalizedPosition.lat.toFixed(7);
                lngInput.value = normalizedPosition.lng.toFixed(7);

                if (! marker) {
                    marker = new google.maps.Marker({
                        position: normalizedPosition,
                        map,
                        draggable: true,
                        title: 'Vị trí điểm danh',
                    });

                    marker.addListener('dragend', (event) => setLocation(event.latLng, { pan: false }));
                } else {
                    marker.setPosition(normalizedPosition);
                }

                circle.setCenter(normalizedPosition);
                circle.setRadius(readRadius());
                circle.setVisible(true);

                if (options.pan !== false) {
                    map.panTo(normalizedPosition);
                }

                setStatus(`Đã chọn tọa độ ${normalizedPosition.lat.toFixed(7)}, ${normalizedPosition.lng.toFixed(7)} với bán kính ${readRadius()}m.`, 'success');
            }

            function syncMarkerFromInputs() {
                const lat = Number(latInput.value);
                const lng = Number(lngInput.value);

                if (! Number.isFinite(lat) || ! Number.isFinite(lng)) {
                    return;
                }

                setLocation({ lat, lng });
            }

            window.gm_authFailure = function () {
                setStatus('Google Maps API key không hợp lệ hoặc chưa bật Maps JavaScript API. Vẫn có thể nhập tọa độ thủ công.', 'danger');
            };

            window.initActivityLocationMap = function () {
                if (! mapElement) {
                    return;
                }

                map = new google.maps.Map(mapElement, {
                    center: config.center,
                    zoom: config.hasSelectedLocation ? 17 : 16,
                    mapTypeControl: false,
                    streetViewControl: false,
                    fullscreenControl: true,
                });

                circle = new google.maps.Circle({
                    map,
                    center: config.center,
                    radius: config.radius,
                    strokeColor: '#2563eb',
                    strokeOpacity: 0.85,
                    strokeWeight: 2,
                    fillColor: '#2563eb',
                    fillOpacity: 0.12,
                    clickable: false,
                    visible: config.hasSelectedLocation,
                });

                if (config.hasSelectedLocation) {
                    setLocation(config.center, { pan: false });
                }

                map.addListener('click', (event) => setLocation(event.latLng));

                radiusInput?.addEventListener('input', () => {
                    const radius = readRadius();
                    circle.setRadius(radius);

                    if (marker) {
                        circle.setCenter(marker.getPosition());
                        setStatus(`Đã cập nhật bán kính GPS: ${radius}m.`, 'success');
                    } else {
                        setStatus(`Bán kính GPS sẽ là ${radius}m sau khi chọn điểm.`, 'secondary');
                    }
                });

                latInput?.addEventListener('change', syncMarkerFromInputs);
                lngInput?.addEventListener('change', syncMarkerFromInputs);

                currentLocationButton?.addEventListener('click', () => {
                    if (! navigator.geolocation) {
                        setStatus('Trình duyệt không hỗ trợ lấy vị trí hiện tại.', 'danger');
                        return;
                    }

                    currentLocationButton.disabled = true;
                    setStatus('Đang lấy vị trí hiện tại...', 'info');

                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            setLocation({
                                lat: position.coords.latitude,
                                lng: position.coords.longitude,
                            });
                            map.setZoom(17);
                            setStatus(`Đã dùng vị trí hiện tại. Sai số GPS khoảng ${Math.round(position.coords.accuracy)}m.`, 'success');
                            currentLocationButton.disabled = false;
                        },
                        () => {
                            setStatus('Không lấy được vị trí hiện tại. Hãy cho phép truy cập vị trí hoặc chọn trực tiếp trên bản đồ.', 'danger');
                            currentLocationButton.disabled = false;
                        },
                        {
                            enableHighAccuracy: true,
                            timeout: 15000,
                            maximumAge: 0,
                        }
                    );
                });
            };
        })();
    </script>
    <script async defer src="https://maps.googleapis.com/maps/api/js?key={{ rawurlencode($googleMapsBrowserKey) }}&callback=initActivityLocationMap"></script>
    @endpush
@endif
@endsection
