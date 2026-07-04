@extends('layouts.doan-hoi')

@section('page-title', $hoatDong->exists ? 'Sửa hoạt động' : 'Tạo hoạt động')

@section('content')
@php
    $defaultAddress = '12 Trịnh Đình Thảo, Tân Phú';
    $googleMapsBrowserKey = $googleMapsBrowserKey ?? null;
    $hasGoogleMapsBrowserKey = is_string($googleMapsBrowserKey) && str_starts_with($googleMapsBrowserKey, 'AIza');
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

        <div class="col-md-8">
            <label class="form-label" for="dia_diem">Địa điểm</label>
            <input class="form-control" id="dia_diem" name="dia_diem" maxlength="255" aria-describedby="dia_diem_hint" value="{{ old('dia_diem', $hoatDong->dia_diem ?: $defaultAddress) }}">
            <input type="hidden" name="location_lat" id="latitude" value="{{ $locationLatValue }}">
            <input type="hidden" name="location_lng" id="longitude" value="{{ $locationLngValue }}">
            <div class="form-text text-secondary" id="dia_diem_hint">Tên địa điểm giúp sinh viên nhận biết nơi diễn ra hoạt động.</div>
        </div>

        <div class="col-md-4">
            <label class="form-label" for="location_radius_meters">Bán kính GPS (m)</label>
            <input class="form-control" id="location_radius_meters" type="number" step="1" min="10" max="1000" name="location_radius_meters" aria-describedby="location_radius_hint" value="{{ $locationRadiusValue }}">
            <div class="form-text text-secondary" id="location_radius_hint">Sinh viên cần ở trong bán kính này để điểm danh.</div>
        </div>

        <div class="col-12">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-2 mb-2">
                <div>
                    <label class="form-label mb-1">Bản đồ chọn vị trí</label>
                    <div class="small text-secondary">
                        @if ($hasGoogleMapsBrowserKey)
                            Nhập địa điểm, chọn gợi ý, click vào bản đồ hoặc kéo ghim để cập nhật tọa độ. Vòng tròn thể hiện bán kính GPS.
                        @else
                            Nhập địa điểm rồi bấm tìm, click vào bản đồ hoặc kéo ghim để cập nhật tọa độ. Vòng tròn thể hiện bán kính GPS.
                        @endif
                    </div>
                </div>
                <div class="d-flex gap-2">
                    @unless ($hasGoogleMapsBrowserKey)
                        <button class="btn btn-outline-primary btn-sm" type="button" id="search-location">
                            <i class="bi bi-search mr-1"></i>Tìm trên bản đồ
                        </button>
                    @endunless
                    <button class="btn btn-outline-primary btn-sm" type="button" id="use-current-location">
                        <i class="bi bi-crosshair mr-1"></i>Dùng vị trí hiện tại
                    </button>
                </div>
            </div>

            <div id="activity-map" role="application" aria-label="Bản đồ chọn tọa độ hoạt động"></div>
            <div id="location-map-status" class="small text-secondary mt-2">
                {{ $hasSelectedLocation ? 'Đang hiển thị tọa độ đã lưu.' : 'Chưa chọn tọa độ. Bản đồ đang mở gần địa điểm mặc định.' }}
            </div>
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
@unless ($hasGoogleMapsBrowserKey)
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
@endunless
<style>
    .required-marker {
        color: #dc3545;
        font-weight: 700;
    }

    #activity-map {
        height: 350px;
        width: 100%;
        border: 1px solid #ced4da;
        border-radius: 8px;
        overflow: hidden;
        background: #f8fafc;
    }
    .leaflet-container {
        font-family: inherit;
    }
</style>
@endpush

@if ($hasGoogleMapsBrowserKey)
    @push('scripts')
    <script>
        (function () {
            const config = @json($mapConfig);

            let map;
            let marker;
            let circle;

            const mapElement = document.getElementById('activity-map');
            const statusElement = document.getElementById('location-map-status');
            const currentLocationButton = document.getElementById('use-current-location');
            const addressInput = document.getElementById('dia_diem');
            const latInput = document.getElementById('latitude');
            const lngInput = document.getElementById('longitude');
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

            window.gm_authFailure = function () {
                setStatus('Google Maps API key không hợp lệ hoặc chưa bật Maps JavaScript API. Không thể chọn vị trí trên bản đồ.', 'danger');
            };

            function initAutocomplete() {
                if (! addressInput || ! google.maps.places?.Autocomplete) {
                    setStatus('Không tải được Google Places Autocomplete. Bạn vẫn có thể chọn trực tiếp trên bản đồ.', 'warning');
                    return;
                }

                const autocomplete = new google.maps.places.Autocomplete(addressInput, {
                    componentRestrictions: { country: 'vn' },
                    fields: ['formatted_address', 'geometry', 'name'],
                });

                autocomplete.bindTo('bounds', map);
                autocomplete.addListener('place_changed', () => {
                    const place = autocomplete.getPlace();

                    if (! place.geometry?.location) {
                        setStatus('Không tìm thấy tọa độ cho địa điểm đã chọn. Hãy chọn trực tiếp trên bản đồ.', 'danger');
                        return;
                    }

                    addressInput.value = place.formatted_address || place.name || addressInput.value;
                    setLocation(place.geometry.location);
                    map.setZoom(17);
                });
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

                initAutocomplete();

                if (config.hasSelectedLocation) {
                    setLocation(config.center, { pan: false });
                } else {
                    marker = new google.maps.Marker({
                        position: config.center,
                        map,
                        draggable: true,
                        title: 'Kéo ghim hoặc click bản đồ để chọn vị trí điểm danh',
                    });

                    marker.addListener('dragend', (event) => setLocation(event.latLng, { pan: false }));
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
    <script async defer src="https://maps.googleapis.com/maps/api/js?key={{ rawurlencode($googleMapsBrowserKey) }}&libraries=places&callback=initActivityLocationMap"></script>
    @endpush
@else
    @push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        (function () {
            const config = @json($mapConfig);

            let map;
            let marker;
            let circle;

            const mapElement = document.getElementById('activity-map');
            const statusElement = document.getElementById('location-map-status');
            const currentLocationButton = document.getElementById('use-current-location');
            const searchLocationButton = document.getElementById('search-location');
            const addressInput = document.getElementById('dia_diem');
            const latInput = document.getElementById('latitude');
            const lngInput = document.getElementById('longitude');
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
                const lng = typeof position.lng === 'function' ? position.lng() : Number(position.lng ?? position.lon);

                if (! Number.isFinite(lat) || ! Number.isFinite(lng) || lat < -90 || lat > 90 || lng < -180 || lng > 180) {
                    return null;
                }

                return { lat, lng };
            }

            function setLocation(position, options = {}) {
                const normalizedPosition = normalizePosition(position);

                if (! normalizedPosition || ! map) {
                    return;
                }

                const latLng = [normalizedPosition.lat, normalizedPosition.lng];
                latInput.value = normalizedPosition.lat.toFixed(7);
                lngInput.value = normalizedPosition.lng.toFixed(7);

                if (! marker) {
                    marker = L.marker(latLng, {
                        draggable: true,
                        title: 'Vị trí điểm danh',
                    }).addTo(map);

                    marker.on('dragend', () => setLocation(marker.getLatLng(), { pan: false }));
                } else {
                    marker.setLatLng(latLng);
                }

                if (! circle) {
                    circle = L.circle(latLng, {
                        radius: readRadius(),
                        color: '#2563eb',
                        weight: 2,
                        opacity: 0.85,
                        fillColor: '#2563eb',
                        fillOpacity: 0.12,
                        interactive: false,
                    }).addTo(map);
                } else {
                    circle.setLatLng(latLng);
                    circle.setRadius(readRadius());
                }

                if (options.pan !== false) {
                    map.panTo(latLng);
                }

                setStatus(`Đã chọn tọa độ ${normalizedPosition.lat.toFixed(7)}, ${normalizedPosition.lng.toFixed(7)} với bán kính ${readRadius()}m.`, 'success');
            }

            async function searchAddress() {
                const query = addressInput?.value.trim();

                if (! query || query.length < 3) {
                    setStatus('Nhập địa điểm rõ hơn trước khi tìm trên bản đồ.', 'danger');
                    return;
                }

                searchLocationButton.disabled = true;
                setStatus('Đang tìm địa điểm trên OpenStreetMap...', 'info');

                try {
                    const url = new URL('https://nominatim.openstreetmap.org/search');
                    url.searchParams.set('format', 'jsonv2');
                    url.searchParams.set('limit', '1');
                    url.searchParams.set('countrycodes', 'vn');
                    url.searchParams.set('q', query);

                    const response = await fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                        },
                    });

                    if (! response.ok) {
                        throw new Error('Không tìm được địa điểm từ OpenStreetMap.');
                    }

                    const results = await response.json();
                    const result = results[0];

                    if (! result) {
                        setStatus('Không tìm thấy địa điểm. Hãy click trực tiếp trên bản đồ để chọn.', 'danger');
                        return;
                    }

                    addressInput.value = result.display_name || query;
                    const position = { lat: result.lat, lng: result.lon };
                    setLocation(position);
                    map.setView([Number(result.lat), Number(result.lon)], 17);
                } catch (error) {
                    setStatus(error.message || 'Không tìm được địa điểm. Hãy click trực tiếp trên bản đồ để chọn.', 'danger');
                } finally {
                    searchLocationButton.disabled = false;
                }
            }

            function initLeafletActivityMap() {
                if (! mapElement || ! window.L) {
                    setStatus('Không tải được bản đồ OpenStreetMap.', 'danger');
                    return;
                }

                const center = [config.center.lat, config.center.lng];
                map = L.map(mapElement).setView(center, config.hasSelectedLocation ? 17 : 16);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors',
                }).addTo(map);

                marker = L.marker(center, {
                    draggable: true,
                    title: 'Kéo ghim hoặc click bản đồ để chọn vị trí điểm danh',
                }).addTo(map);

                marker.on('dragend', () => setLocation(marker.getLatLng(), { pan: false }));
                map.on('click', (event) => setLocation(event.latlng));

                if (config.hasSelectedLocation) {
                    setLocation(config.center, { pan: false });
                } else {
                    setStatus('Đang dùng OpenStreetMap miễn phí. Click bản đồ, kéo ghim hoặc tìm địa điểm để chọn tọa độ.', 'secondary');
                }

                radiusInput?.addEventListener('input', () => {
                    const radius = readRadius();

                    if (circle) {
                        circle.setRadius(radius);
                        setStatus(`Đã cập nhật bán kính GPS: ${radius}m.`, 'success');
                    } else {
                        setStatus(`Bán kính GPS sẽ là ${radius}m sau khi chọn điểm.`, 'secondary');
                    }
                });

                searchLocationButton?.addEventListener('click', searchAddress);
                addressInput?.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        searchAddress();
                    }
                });

                currentLocationButton?.addEventListener('click', () => {
                    if (! navigator.geolocation) {
                        setStatus('Trình duyệt không hỗ trợ lấy vị trí hiện tại.', 'danger');
                        return;
                    }

                    currentLocationButton.disabled = true;
                    setStatus('Đang lấy vị trí hiện tại...', 'info');

                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            const selectedPosition = {
                                lat: position.coords.latitude,
                                lng: position.coords.longitude,
                            };

                            setLocation(selectedPosition);
                            map.setView([selectedPosition.lat, selectedPosition.lng], 17);
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
            }

            initLeafletActivityMap();
        })();
    </script>
    @endpush
@endif
@endsection
