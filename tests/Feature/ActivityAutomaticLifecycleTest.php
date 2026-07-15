<?php

namespace Tests\Feature;

use App\Models\DangKyHoatDong;
use App\Models\HoatDong;
use App\Models\Khoa;
use App\Models\Lop;
use App\Models\SinhVien;
use App\Models\StudentNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ActivityAutomaticLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_scheduler_transitions_each_boundary_and_notifications_are_idempotent(): void
    {
        $this->travelTo(Carbon::parse('2026-07-15 00:00:00', 'UTC'));
        [$student, $activity] = $this->fixture(10, now()->addHours(12));

        $this->artisan('activities:auto-status')->assertSuccessful();
        $this->artisan('activities:auto-status')->assertSuccessful();
        $this->assertSame(HoatDong::STATUS_SCHEDULED, $activity->refresh()->trang_thai);
        $this->assertSame(1, StudentNotification::query()
            ->where('user_id', $student->user_id)
            ->where('dedupe_key', "activity:{$activity->id}:opening-soon:24-hours")->count());

        $this->travelTo($activity->open_registration_at);
        $this->artisan('activities:auto-status')->assertSuccessful();
        $this->artisan('activities:auto-status')->assertSuccessful();
        $this->assertSame(HoatDong::STATUS_OPEN, $activity->refresh()->trang_thai);
        $this->assertSame(1, StudentNotification::query()
            ->where('user_id', $student->user_id)
            ->where('dedupe_key', "activity:{$activity->id}:open")->count());
        $this->assertDatabaseHas('logs', ['hanh_dong' => 'activity.status_changed', 'doi_tuong_id' => $activity->id]);

        $this->travelTo($activity->close_registration_at);
        $this->artisan('activities:auto-status')->assertSuccessful();
        $this->assertSame(HoatDong::STATUS_REGISTRATION_CLOSED, $activity->refresh()->trang_thai);

        $this->travelTo($activity->thoi_gian_ket_thuc);
        $this->artisan('activities:auto-status')->assertSuccessful();
        $this->assertSame(HoatDong::STATUS_COMPLETED, $activity->refresh()->trang_thai);
    }

    public function test_registration_is_immediately_approved_and_capacity_and_duplicate_are_enforced(): void
    {
        $this->travelTo(Carbon::parse('2026-07-15 00:00:00', 'UTC'));
        [$student, $activity, $otherStudent] = $this->fixture(1, now()->subMinute());

        $this->actingAs($student->user)->postJson(route('sinh-vien.activities.register', $activity))
            ->assertOk()->assertJsonPath('registration.status', 'approved')
            ->assertJsonPath('registered_count', 1)->assertJsonPath('remaining_slots', 0);
        $registration = DangKyHoatDong::query()->firstOrFail();
        $this->assertSame('approved', $registration->trang_thai);
        $this->assertNotNull($registration->registered_at);
        $this->assertSame(HoatDong::STATUS_OPEN, $activity->refresh()->trang_thai);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $student->user_id,
            'dedupe_key' => "activity:{$activity->id}:registration:{$student->user_id}",
        ]);

        $this->actingAs($student->user)->postJson(route('sinh-vien.activities.register', $activity))
            ->assertUnprocessable()->assertJsonStructure(['errors' => ['hoat_dong']]);
        $this->actingAs($otherStudent->user)->postJson(route('sinh-vien.activities.register', $activity))
            ->assertUnprocessable()->assertJsonStructure(['errors' => ['hoat_dong']]);
        $this->assertSame(1, DangKyHoatDong::query()->count());
    }

    public function test_student_cannot_register_before_open_at_close_boundary_or_for_another_faculty(): void
    {
        $this->travelTo(Carbon::parse('2026-07-15 00:00:00', 'UTC'));
        [$student, $activity] = $this->fixture(10, now()->addHour());

        $this->actingAs($student->user)->postJson(route('sinh-vien.activities.register', $activity))
            ->assertUnprocessable();
        $this->travelTo($activity->close_registration_at);
        $this->actingAs($student->user)->postJson(route('sinh-vien.activities.register', $activity))
            ->assertUnprocessable();

        $otherFaculty = Khoa::create(['ma_khoa' => 'KT', 'ten_khoa' => 'Kinh te', 'is_active' => true]);
        $activity->khoas()->sync([$otherFaculty->id]);
        $this->actingAs($student->user)->get(route('sinh-vien.activities.show', $activity))->assertForbidden();
    }

    private function fixture(int $capacity, Carbon $openAt): array
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permission = Permission::findOrCreate('self evaluate', 'web');
        $role = Role::findOrCreate('sinh_vien', 'web');
        $role->givePermissionTo($permission);
        $faculty = Khoa::create(['ma_khoa' => 'CNTT', 'ten_khoa' => 'Cong nghe thong tin', 'is_active' => true]);
        $class = Lop::create(['khoa_id' => $faculty->id, 'ma_lop' => 'D21', 'ten_lop' => 'D21', 'is_active' => true]);
        $student = $this->student($class, 'SV001');
        $otherStudent = $this->student($class, 'SV002');
        $organizer = User::factory()->create(['is_active' => true]);
        $closeAt = $openAt->copy()->addHours(2);

        $activity = HoatDong::create([
            'user_id' => $organizer->id, 'ma_hoat_dong' => 'HD-AUTO',
            'ten_hoat_dong' => 'Hien mau nhan dao', 'loai_hoat_dong' => 'Doan Hoi',
            'dia_diem' => 'Hoi truong A', 'open_registration_at' => $openAt,
            'close_registration_at' => $closeAt, 'thoi_gian_bat_dau' => $closeAt->copy()->addHour(),
            'thoi_gian_ket_thuc' => $closeAt->copy()->addHours(3), 'so_luong_toi_da' => $capacity,
            'diem_cong' => 5, 'trang_thai' => HoatDong::STATUS_SCHEDULED,
        ]);

        return [$student, $activity, $otherStudent];
    }

    private function student(Lop $class, string $code): SinhVien
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('sinh_vien');

        return SinhVien::create([
            'user_id' => $user->id, 'lop_id' => $class->id, 'ma_sinh_vien' => $code,
            'ho_ten' => "Sinh vien {$code}", 'trang_thai' => 'dang_hoc',
        ]);
    }
}
