<?php

namespace Tests\Feature;

use App\Models\HoatDong;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DoanHoiActivityLocationFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_form_uses_hidden_coordinates_and_loads_places_library(): void
    {
        config(['services.google_maps.browser_key' => 'AIzaFakeBrowserKey']);

        $response = $this->actingAs($this->makeStaff())
            ->get(route('doan-hoi.activities.create'));

        $response->assertOk();

        $html = $response->getContent();
        $this->assertStringNotContainsString('for="location_lat">Latitude', $html);
        $this->assertStringNotContainsString('for="location_lng">Longitude', $html);
        $this->assertStringNotContainsString('type="number" step="0.0000001" min="-90" max="90" name="location_lat"', $html);
        $this->assertStringNotContainsString('type="number" step="0.0000001" min="-180" max="180" name="location_lng"', $html);
        $this->assertStringContainsString('type="hidden" name="location_lat" id="latitude"', $html);
        $this->assertStringContainsString('type="hidden" name="location_lng" id="longitude"', $html);
        $this->assertStringContainsString('id="activity-map"', $html);
        $this->assertStringContainsString('maps.googleapis.com/maps/api/js', $html);
        $this->assertStringContainsString('libraries=places', $html);
    }

    public function test_activity_form_uses_leaflet_without_google_maps_key(): void
    {
        config(['services.google_maps.browser_key' => null]);

        $response = $this->actingAs($this->makeStaff())
            ->get(route('doan-hoi.activities.create'));

        $response->assertOk();

        $html = $response->getContent();
        $this->assertStringContainsString('unpkg.com/leaflet@1.9.4/dist/leaflet.css', $html);
        $this->assertStringContainsString('unpkg.com/leaflet@1.9.4/dist/leaflet.js', $html);
        $this->assertStringContainsString('tile.openstreetmap.org', $html);
        $this->assertStringContainsString('id="search-location"', $html);
        $this->assertStringContainsString('type="hidden" name="location_lat" id="latitude"', $html);
        $this->assertStringContainsString('type="hidden" name="location_lng" id="longitude"', $html);
        $this->assertStringNotContainsString('maps.googleapis.com/maps/api/js', $html);
    }

    public function test_activity_uses_leaflet_when_configured_key_is_not_a_maps_browser_key(): void
    {
        config(['services.google_maps.browser_key' => 'AQ.not-a-maps-key']);

        $response = $this->actingAs($this->makeStaff())
            ->get(route('doan-hoi.activities.create'));

        $response->assertOk();

        $html = $response->getContent();
        $this->assertStringContainsString('unpkg.com/leaflet@1.9.4/dist/leaflet.js', $html);
        $this->assertStringContainsString('tile.openstreetmap.org', $html);
        $this->assertStringNotContainsString('maps.googleapis.com/maps/api/js', $html);
    }

    public function test_activity_requires_location_when_no_map_position_is_selected_without_google_maps_key(): void
    {
        config(['services.google_maps.browser_key' => null]);

        $this->actingAs($this->makeStaff())
            ->post(route('doan-hoi.activities.store'), $this->validActivityPayload([
                'location_lat' => null,
                'location_lng' => null,
            ]))
            ->assertSessionHasErrors([
                'location_lat' => 'Vui lòng chọn vị trí trên bản đồ trước khi lưu hoạt động.',
            ]);
    }

    public function test_activity_requires_location_when_no_map_position_is_selected(): void
    {
        config(['services.google_maps.browser_key' => 'fake-browser-key']);

        $this->actingAs($this->makeStaff())
            ->post(route('doan-hoi.activities.store'), $this->validActivityPayload([
                'location_lat' => null,
                'location_lng' => null,
            ]))
            ->assertSessionHasErrors([
                'location_lat' => 'Vui lòng chọn vị trí trên bản đồ trước khi lưu hoạt động.',
            ]);
    }

    public function test_activity_location_is_saved_from_hidden_inputs(): void
    {
        config(['services.google_maps.browser_key' => null]);

        $this->actingAs($this->makeStaff())
            ->post(route('doan-hoi.activities.store'), $this->validActivityPayload([
                'dia_diem' => '12 Trịnh Đình Thảo, Tân Phú',
                'location_lat' => '10.7749241',
                'location_lng' => '106.6345254',
                'location_radius_meters' => '150',
            ]))
            ->assertRedirect(route('doan-hoi.activities.index'));

        $activity = HoatDong::where('ma_hoat_dong', 'HD-GMAP')->firstOrFail();

        $this->assertSame('12 Trịnh Đình Thảo, Tân Phú', $activity->dia_diem);
        $this->assertEqualsWithDelta(10.7749241, $activity->location_lat, 0.0000001);
        $this->assertEqualsWithDelta(106.6345254, $activity->location_lng, 0.0000001);
        $this->assertSame(150, (int) $activity->location_radius_meters);
    }

    public function test_edit_activity_loads_existing_location_into_hidden_inputs_and_map_config(): void
    {
        config(['services.google_maps.browser_key' => null]);

        $staff = $this->makeStaff();
        $activity = HoatDong::create([
            'user_id' => $staff->id,
            'ma_hoat_dong' => 'HD-EDIT',
            'ten_hoat_dong' => 'Hoạt động đã có GPS',
            'loai_hoat_dong' => 'Kỹ năng mềm',
            'dia_diem' => '12 Trịnh Đình Thảo, Tân Phú',
            'location_lat' => 10.7749241,
            'location_lng' => 106.6345254,
            'location_radius_meters' => 120,
            'diem_cong' => 5,
            'trang_thai' => 'open',
            'auto_cong_diem' => true,
        ]);

        $response = $this->actingAs($staff)
            ->get(route('doan-hoi.activities.edit', $activity));

        $response->assertOk();

        $html = $response->getContent();
        $this->assertStringContainsString('name="location_lat" id="latitude" value="10.7749241"', $html);
        $this->assertStringContainsString('name="location_lng" id="longitude" value="106.6345254"', $html);
        $this->assertStringContainsString('"hasSelectedLocation":true', $html);
        $this->assertStringContainsString('"center":{"lat":10.7749241,"lng":106.6345254}', $html);
    }

    public function test_open_activity_can_update_future_schedule_without_regressing_status(): void
    {
        $staff = $this->makeStaff();
        $activity = HoatDong::create([
            'user_id' => $staff->id,
            'ma_hoat_dong' => 'HD-OPEN-EDIT',
            'ten_hoat_dong' => 'Hoạt động đang mở',
            'loai_hoat_dong' => 'Kỹ năng mềm',
            'dia_diem' => '12 Trịnh Đình Thảo, Tân Phú',
            'location_lat' => 10.7749241,
            'location_lng' => 106.6345254,
            'location_radius_meters' => 100,
            'open_registration_at' => now()->subHour(),
            'close_registration_at' => now()->addDay(),
            'thoi_gian_bat_dau' => now()->addDays(2),
            'thoi_gian_ket_thuc' => now()->addDays(2)->addHours(2),
            'diem_cong' => 5,
            'trang_thai' => HoatDong::STATUS_OPEN,
        ]);
        $displayNow = Carbon::now(config('app.display_timezone'))->startOfMinute();

        $this->actingAs($staff)
            ->put(route('doan-hoi.activities.update', $activity), $this->validActivityPayload([
                'ma_hoat_dong' => $activity->ma_hoat_dong,
                'open_registration_at' => $displayNow->copy()->subHour()->format('Y-m-d\TH:i'),
                'close_registration_at' => $displayNow->copy()->addDays(2)->format('Y-m-d\TH:i'),
                'thoi_gian_bat_dau' => $displayNow->copy()->addDays(3)->format('Y-m-d\TH:i'),
                'thoi_gian_ket_thuc' => $displayNow->copy()->addDays(3)->addHours(3)->format('Y-m-d\TH:i'),
            ]))
            ->assertRedirect(route('doan-hoi.activities.index'));

        $activity->refresh();
        $this->assertSame(HoatDong::STATUS_OPEN, $activity->trang_thai);
        $this->assertSame(
            $displayNow->copy()->addDays(3)->utc()->format('Y-m-d H:i:s'),
            $activity->thoi_gian_bat_dau->utc()->format('Y-m-d H:i:s'),
        );
        $this->assertSame(
            $displayNow->copy()->addDays(2)->utc()->format('Y-m-d H:i:s'),
            $activity->close_registration_at->utc()->format('Y-m-d H:i:s'),
        );
    }

    private function makeStaff(): User
    {
        $role = Role::firstOrCreate(['name' => 'can_bo_doan_hoi', 'guard_name' => 'web']);
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        return $user;
    }

    private function validActivityPayload(array $overrides = []): array
    {
        return array_replace([
            'ma_hoat_dong' => 'HD-GMAP',
            'ten_hoat_dong' => 'Hoạt động Google Maps',
            'loai_hoat_dong' => 'Kỹ năng mềm',
            'dia_diem' => '12 Trịnh Đình Thảo, Tân Phú',
            'location_lat' => '10.7749241',
            'location_lng' => '106.6345254',
            'location_radius_meters' => '100',
            'diem_cong' => '5',
            'open_registration_at' => Carbon::now(config('app.display_timezone'))->addDay()->format('Y-m-d\TH:i'),
            'close_registration_at' => Carbon::now(config('app.display_timezone'))->addDays(2)->format('Y-m-d\TH:i'),
            'thoi_gian_bat_dau' => Carbon::now(config('app.display_timezone'))->addDays(3)->format('Y-m-d\TH:i'),
            'thoi_gian_ket_thuc' => Carbon::now(config('app.display_timezone'))->addDays(3)->addHours(2)->format('Y-m-d\TH:i'),
            'auto_cong_diem' => '1',
        ], $overrides);
    }
}
