<?php

namespace Tests\Feature;

use App\Models\AttendanceSession;
use App\Models\ConductPointLog;
use App\Models\DangKyHoatDong;
use App\Models\HoatDong;
use App\Models\Khoa;
use App\Models\Lop;
use App\Models\SinhVien;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AttendanceQrGpsTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_must_check_in_and_check_out_before_points_are_awarded(): void
    {
        [$studentUser, $student, $staff, $activity] = $this->makeAttendanceData();

        $checkIn = $this->actingAs($staff)->postJson(route('api.attendance.sessions.store'), [
            'activityId' => $activity->id,
            'type' => 'check_in',
            'startAt' => now()->subMinute()->toDateTimeString(),
            'endAt' => now()->addMinutes(30)->toDateTimeString(),
        ])->assertOk()->json('session.id');

        $checkInSession = AttendanceSession::findOrFail($checkIn);

        $this->actingAs($studentUser)->postJson(route('api.attendance.scan'), [
            'sessionId' => $checkInSession->id,
            'token' => $checkInSession->token,
            'latitude' => 10.0001,
            'longitude' => 106.0001,
            'accuracy' => 20,
        ])->assertOk()->assertJsonPath('status', 'not_completed');

        $this->assertDatabaseMissing('conduct_point_logs', [
            'sinh_vien_id' => $student->id,
            'hoat_dong_id' => $activity->id,
        ]);

        $checkOut = $this->actingAs($staff)->postJson(route('api.attendance.sessions.store'), [
            'activityId' => $activity->id,
            'type' => 'check_out',
            'startAt' => now()->subMinute()->toDateTimeString(),
            'endAt' => now()->addMinutes(30)->toDateTimeString(),
        ])->assertOk()->json('session.id');

        $checkOutSession = AttendanceSession::findOrFail($checkOut);

        $this->actingAs($studentUser)->postJson(route('api.attendance.scan'), [
            'sessionId' => $checkOutSession->id,
            'token' => $checkOutSession->token,
            'latitude' => 10.0001,
            'longitude' => 106.0001,
            'accuracy' => 20,
        ])->assertOk()->assertJsonPath('status', 'completed');

        $this->actingAs($staff)->postJson(route('api.attendance.approve', $activity))
            ->assertOk()
            ->assertJsonPath('approved_count', 1);

        $this->assertDatabaseHas('conduct_point_logs', [
            'sinh_vien_id' => $student->id,
            'hoat_dong_id' => $activity->id,
            'point' => 5,
        ]);

        $this->actingAs($staff)->postJson(route('api.attendance.approve', $activity))
            ->assertOk()
            ->assertJsonPath('approved_count', 0);

        $this->assertSame(1, ConductPointLog::where('sinh_vien_id', $student->id)->where('hoat_dong_id', $activity->id)->count());
    }

    public function test_scan_is_rejected_when_student_is_outside_allowed_radius(): void
    {
        [$studentUser, , $staff, $activity] = $this->makeAttendanceData();

        $sessionId = $this->actingAs($staff)->postJson(route('api.attendance.sessions.store'), [
            'activityId' => $activity->id,
            'type' => 'check_in',
            'startAt' => now()->subMinute()->toDateTimeString(),
            'endAt' => now()->addMinutes(30)->toDateTimeString(),
        ])->assertOk()->json('session.id');

        $session = AttendanceSession::findOrFail($sessionId);

        $this->actingAs($studentUser)->postJson(route('api.attendance.scan'), [
            'sessionId' => $session->id,
            'token' => $session->token,
            'latitude' => 11,
            'longitude' => 107,
            'accuracy' => 20,
        ])->assertUnprocessable();
    }

    private function makeAttendanceData(): array
    {
        $studentRole = Role::create(['name' => 'sinh_vien', 'guard_name' => 'web']);
        $staffRole = Role::create(['name' => 'can_bo_doan_hoi', 'guard_name' => 'web']);

        $khoa = Khoa::create([
            'ma_khoa' => 'CNTT',
            'ten_khoa' => 'Công nghệ thông tin',
            'is_active' => true,
        ]);

        $lop = Lop::create([
            'khoa_id' => $khoa->id,
            'ma_lop' => 'D21CNTT01',
            'ten_lop' => 'D21CNTT01',
            'is_active' => true,
        ]);

        $studentUser = User::factory()->create([
            'ma_dang_nhap' => 'SV001',
            'is_active' => true,
        ]);
        $studentUser->assignRole($studentRole);

        $student = SinhVien::create([
            'user_id' => $studentUser->id,
            'lop_id' => $lop->id,
            'ma_sinh_vien' => 'SV001',
            'ho_ten' => 'Nguyễn An',
            'trang_thai' => 'dang_hoc',
        ]);

        $staff = User::factory()->create([
            'ma_dang_nhap' => 'doanhoi01',
            'is_active' => true,
        ]);
        $staff->assignRole($staffRole);

        $activity = HoatDong::create([
            'user_id' => $staff->id,
            'ma_hoat_dong' => 'HD-GPS',
            'ten_hoat_dong' => 'Hoạt động GPS',
            'loai_hoat_dong' => 'Kỹ năng mềm',
            'dia_diem' => '12 Trịnh Đình Thảo, Tân Phú',
            'location_lat' => 10,
            'location_lng' => 106,
            'location_radius_meters' => 100,
            'diem_cong' => 5,
            'trang_thai' => 'open',
            'auto_cong_diem' => true,
        ]);

        DangKyHoatDong::create([
            'hoat_dong_id' => $activity->id,
            'sinh_vien_id' => $student->id,
            'trang_thai' => 'approved',
            'approved_by' => $staff->id,
            'approved_at' => now(),
        ]);

        return [$studentUser, $student, $staff, $activity];
    }
}
