<?php

namespace Tests\Feature;

use App\Events\ActivityOpenedEvent;
use App\Events\EvaluationClosedEvent;
use App\Events\EvaluationOpenedEvent;
use App\Models\DotDanhGia;
use App\Models\HoatDong;
use App\Models\HocKy;
use App\Models\Khoa;
use App\Models\Lop;
use App\Models\NamHoc;
use App\Models\PhieuDanhGia;
use App\Models\SinhVien;
use App\Models\StudentNotification;
use App\Models\ThongBao;
use App\Models\User;
use App\Services\DotDanhGiaService;
use App\Services\ActivityLifecycleService;
use App\Services\StudentNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class StudentNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_open_notification_targets_active_students_and_is_idempotent(): void
    {
        $data = $this->baseData();
        $active = $this->student($data, 'SV001', 'dang_hoc');
        $paused = $this->student($data, 'SV002', 'bao_luu');
        $period = $this->period($data, now()->subHour(), now()->addDays(5));

        $service = app(StudentNotificationService::class);
        $this->assertSame(1, $service->evaluationOpened($period));
        $this->assertSame(0, $service->evaluationOpened($period));

        $this->assertDatabaseHas('notifications', [
            'user_id' => $active->user_id,
            'type' => StudentNotification::TYPE_EVALUATION_OPEN,
            'related_id' => $period->id,
        ]);
        $this->assertDatabaseMissing('notifications', ['user_id' => $paused->user_id]);
        $this->assertSame(1, StudentNotification::query()->count());
        $this->assertStringContainsString('Học kỳ 1', StudentNotification::query()->first()->content);
    }

    public function test_reminders_only_target_incomplete_students_at_each_milestone(): void
    {
        $this->travelTo(Carbon::parse('2026-07-15 00:00:00', 'UTC'));
        $data = $this->baseData();
        $incomplete = $this->student($data, 'SV010', 'dang_hoc');
        $complete = $this->student($data, 'SV011', 'dang_hoc');
        $period = $this->period($data, now()->subDay(), now()->addHours(72));

        PhieuDanhGia::create([
            'sinh_vien_id' => $complete->id,
            'hoc_ky_id' => $data['hocKy']->id,
            'dot_danh_gia_id' => $period->id,
            'trang_thai' => PhieuDanhGia::STATUS_SUBMITTED,
        ]);

        $this->artisan('notifications:reconcile')->assertSuccessful();
        $this->artisan('notifications:reconcile')->assertSuccessful();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $incomplete->user_id,
            'type' => StudentNotification::TYPE_EVALUATION_REMINDER,
            'dedupe_key' => "evaluation:{$period->id}:reminder:3-days",
        ]);
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $complete->user_id,
            'type' => StudentNotification::TYPE_EVALUATION_REMINDER,
        ]);

        $this->travelTo(now()->addHours(48));
        $this->artisan('notifications:reconcile')->assertSuccessful();
        $this->assertSame(2, StudentNotification::query()
            ->where('user_id', $incomplete->user_id)
            ->where('type', StudentNotification::TYPE_EVALUATION_REMINDER)
            ->count());
    }

    public function test_lifecycle_dispatches_open_and_closed_events_once_per_transition(): void
    {
        $data = $this->baseData();
        $period = $this->period($data, now()->subMinute(), now()->addMinute());
        Event::fake([EvaluationOpenedEvent::class, EvaluationClosedEvent::class]);

        app(DotDanhGiaService::class)->syncPeriod($period);
        app(DotDanhGiaService::class)->syncPeriod($period->refresh());
        Event::assertDispatchedTimes(EvaluationOpenedEvent::class, 1);

        $this->travelTo(now()->addMinutes(2));
        app(DotDanhGiaService::class)->syncPeriod($period->refresh());
        app(DotDanhGiaService::class)->syncPeriod($period->refresh());
        Event::assertDispatchedTimes(EvaluationClosedEvent::class, 1);
    }

    public function test_student_can_read_own_notification_but_not_another_students(): void
    {
        $data = $this->baseData();
        $first = $this->student($data, 'SV020', 'dang_hoc');
        $second = $this->student($data, 'SV021', 'dang_hoc');
        $own = $this->notification($first->user_id, 'own');
        $other = $this->notification($second->user_id, 'other');

        $this->actingAs($first->user)
            ->get(route('sinh-vien.notifications.index', ['filter' => 'unread']))
            ->assertOk()
            ->assertSee('Thông báo own')
            ->assertDontSee('Thông báo other');

        $this->actingAs($first->user)
            ->patch(route('sinh-vien.notifications.read', $other))
            ->assertForbidden();

        $this->actingAs($first->user)
            ->patch(route('sinh-vien.notifications.read', $own))
            ->assertRedirect();
        $this->assertTrue($own->refresh()->is_read);
        $this->assertFalse($other->refresh()->is_read);
    }

    public function test_activity_transition_and_admin_announcement_are_distributed_without_duplicates(): void
    {
        $data = $this->baseData();
        $student = $this->student($data, 'SV030', 'dang_hoc');
        Event::fake([ActivityOpenedEvent::class]);

        $activity = HoatDong::create([
            'user_id' => $data['admin']->id,
            'ma_hoat_dong' => 'HD01',
            'ten_hoat_dong' => 'Ngày hội sinh viên',
            'loai_hoat_dong' => 'Đoàn Hội',
            'diem_cong' => 2,
            'open_registration_at' => now()->subMinute(),
            'close_registration_at' => now()->addDay(),
            'thoi_gian_bat_dau' => now()->addDays(2),
            'thoi_gian_ket_thuc' => now()->addDays(2)->addHours(2),
            'trang_thai' => HoatDong::STATUS_SCHEDULED,
        ]);
        $activity->update(['ten_hoat_dong' => 'Ngày hội sinh viên mới']);
        Event::assertNotDispatched(ActivityOpenedEvent::class);
        app(ActivityLifecycleService::class)->sync($activity);
        app(ActivityLifecycleService::class)->sync($activity->refresh());
        Event::assertDispatchedTimes(ActivityOpenedEvent::class, 1);

        $announcement = ThongBao::create([
            'user_id' => $data['admin']->id,
            'tieu_de' => 'Biểu mẫu mới',
            'noi_dung' => 'Hệ thống đã cập nhật biểu mẫu đánh giá mới.',
            'loai' => 'feature',
            'doi_tuong' => 'sinh_vien',
            'published_at' => now(),
            'is_active' => true,
        ]);
        $service = app(StudentNotificationService::class);
        $this->assertSame(0, $service->announcement($announcement->refresh()));
        $this->assertDatabaseHas('notifications', [
            'user_id' => $student->user_id,
            'type' => StudentNotification::TYPE_SYSTEM_ACTIVITY,
            'title' => 'Biểu mẫu mới',
        ]);
        $this->assertSame(1, StudentNotification::query()->where('dedupe_key', "announcement:{$announcement->id}")->count());
    }

    public function test_admin_can_publish_an_announcement_to_students(): void
    {
        $data = $this->baseData();
        $student = $this->student($data, 'SV040', 'dang_hoc');

        $this->actingAs($data['admin'])->post(route('admin.crud.store', 'thong-baos'), [
            'tieu_de' => 'Mở chức năng xem kết quả',
            'noi_dung' => 'Nhà trường đã mở chức năng xem kết quả đánh giá.',
            'loai' => 'feature',
            'doi_tuong' => 'sinh_vien',
            'published_at' => now()->format('Y-m-d H:i:s'),
            'is_active' => '1',
        ])->assertRedirect(route('admin.crud.index', 'thong-baos'));

        $this->assertDatabaseHas('notifications', [
            'user_id' => $student->user_id,
            'title' => 'Mở chức năng xem kết quả',
            'type' => StudentNotification::TYPE_SYSTEM_ACTIVITY,
        ]);
    }

    private function baseData(): array
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $viewNotifications = Permission::findOrCreate('view student notifications', 'web');
        $manageNotifications = Permission::findOrCreate('manage notifications', 'web');
        $studentRole = Role::findOrCreate('sinh_vien', 'web');
        $studentRole->givePermissionTo($viewNotifications);
        $admin = User::factory()->create(['is_active' => true]);
        $admin->givePermissionTo($manageNotifications);
        $khoa = Khoa::create(['ma_khoa' => 'CNTT', 'ten_khoa' => 'CNTT', 'is_active' => true]);
        $lop = Lop::create(['khoa_id' => $khoa->id, 'ma_lop' => 'D21', 'ten_lop' => 'D21', 'is_active' => true]);
        $namHoc = NamHoc::create(['ten_nam_hoc' => '2026-2027', 'is_active' => true]);
        $hocKy = HocKy::create(['nam_hoc_id' => $namHoc->id, 'ten_hoc_ky' => 'Học kỳ 1', 'thu_tu' => 1, 'is_active' => true]);

        return compact('admin', 'khoa', 'lop', 'namHoc', 'hocKy');
    }

    private function student(array $data, string $code, string $status): SinhVien
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('sinh_vien');

        return SinhVien::create([
            'user_id' => $user->id,
            'lop_id' => $data['lop']->id,
            'ma_sinh_vien' => $code,
            'ho_ten' => "Sinh viên {$code}",
            'trang_thai' => $status,
        ]);
    }

    private function period(array $data, Carbon $start, Carbon $end): DotDanhGia
    {
        return DotDanhGia::create([
            'ten_dot' => 'Đợt đánh giá học kỳ 1 năm học 2026-2027',
            'nam_hoc_id' => $data['namHoc']->id,
            'hoc_ky_id' => $data['hocKy']->id,
            'ngay_bat_dau_sinh_vien' => $start,
            'ngay_ket_thuc_sinh_vien' => $end,
            'ngay_bat_dau_gvcn' => $end,
            'ngay_ket_thuc_gvcn' => $end->copy()->addDay(),
            'ngay_cong_bo' => $end->copy()->addDays(2),
            'trang_thai' => DotDanhGia::STATUS_DRAFT,
            'created_by' => $data['admin']->id,
        ]);
    }

    private function notification(int $userId, string $key): StudentNotification
    {
        return StudentNotification::create([
            'user_id' => $userId,
            'title' => "Thông báo {$key}",
            'content' => "Nội dung {$key}",
            'type' => StudentNotification::TYPE_SYSTEM_ACTIVITY,
            'dedupe_key' => $key,
            'is_read' => false,
        ]);
    }
}
