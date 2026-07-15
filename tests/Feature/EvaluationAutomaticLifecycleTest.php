<?php

namespace Tests\Feature;

use App\Events\EvaluationStatusChanged;
use App\Models\DotDanhGia;
use App\Models\HocKy;
use App\Models\Khoa;
use App\Models\Lop;
use App\Models\NamHoc;
use App\Models\PhieuDanhGia;
use App\Models\SinhVien;
use App\Models\User;
use App\Services\DiemRenLuyenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class EvaluationAutomaticLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_period_boundaries_use_half_open_windows(): void
    {
        $data = $this->baseData();
        $period = $this->period($data, [
            'ngay_bat_dau_sinh_vien' => '2026-07-14 09:00:00',
            'ngay_ket_thuc_sinh_vien' => '2026-07-14 10:00:00',
            'ngay_bat_dau_gvcn' => '2026-07-14 10:00:00',
            'ngay_ket_thuc_gvcn' => '2026-07-14 11:00:00',
            'ngay_cong_bo' => '2026-07-14 12:00:00',
        ]);

        $this->travelTo(Carbon::parse('2026-07-14 08:59:59', 'UTC'));
        $this->assertSame(DotDanhGia::STATUS_DRAFT, $period->effectiveStatus());
        $this->assertFalse($period->isStudentOpen());

        $this->travelTo(Carbon::parse('2026-07-14 09:00:00', 'UTC'));
        $this->assertSame(DotDanhGia::STATUS_OPEN, $period->effectiveStatus());
        $this->assertTrue($period->isStudentOpen());

        $this->travelTo(Carbon::parse('2026-07-14 10:00:00', 'UTC'));
        $this->assertSame(DotDanhGia::STATUS_CLOSED, $period->effectiveStatus());
        $this->assertFalse($period->isStudentOpen());
        $this->assertTrue($period->isGvcnOpen());

        $this->travelTo(Carbon::parse('2026-07-14 11:00:00', 'UTC'));
        $this->assertFalse($period->isGvcnOpen());

        $this->travelTo(Carbon::parse('2026-07-14 12:00:00', 'UTC'));
        $this->assertSame(DotDanhGia::STATUS_PUBLISHED, $period->effectiveStatus());
    }

    public function test_sync_command_locks_and_publishes_idempotently(): void
    {
        $this->travelTo(Carbon::parse('2026-07-14 11:00:00', 'UTC'));
        $data = $this->baseData();
        $period = $this->period($data, [
            'ngay_bat_dau_sinh_vien' => now()->subHours(3),
            'ngay_ket_thuc_sinh_vien' => now()->subHours(2),
            'ngay_bat_dau_gvcn' => now()->subHours(2),
            'ngay_ket_thuc_gvcn' => now(),
            'ngay_cong_bo' => now()->addHour(),
            'trang_thai' => DotDanhGia::STATUS_OPEN,
        ]);

        $submitted = $this->form($data, $period, PhieuDanhGia::STATUS_SUBMITTED);
        $reviewed = $this->form($data, $period, PhieuDanhGia::STATUS_REVIEWED, 1001);
        $approved = $this->form($data, $period, PhieuDanhGia::STATUS_APPROVED, 1002);

        Event::fake([EvaluationStatusChanged::class]);

        $this->artisan('evaluations:sync-statuses')->assertSuccessful();
        $this->artisan('evaluations:sync-statuses')->assertSuccessful();

        $this->assertSame(PhieuDanhGia::STATUS_LOCKED, $submitted->refresh()->trang_thai);
        $this->assertSame(PhieuDanhGia::STATUS_LOCKED, $reviewed->refresh()->trang_thai);
        $this->assertSame(PhieuDanhGia::STATUS_APPROVED, $approved->refresh()->trang_thai);
        Event::assertDispatchedTimes(EvaluationStatusChanged::class, 2);

        $this->travelTo(now()->addHour());
        $this->artisan('evaluations:sync-statuses')->assertSuccessful();
        $this->artisan('evaluations:sync-statuses')->assertSuccessful();

        $this->assertSame(DotDanhGia::STATUS_PUBLISHED, $period->refresh()->trang_thai);
        Event::assertDispatchedTimes(EvaluationStatusChanged::class, 5);
    }

    public function test_future_draft_has_a_specific_warning_and_becomes_editable_at_start(): void
    {
        $this->travelTo(Carbon::parse('2026-07-14 09:00:00', 'UTC'));
        $data = $this->baseData();
        $period = $this->period($data, [
            'ngay_bat_dau_sinh_vien' => now()->addHour(),
            'ngay_ket_thuc_sinh_vien' => now()->addHours(2),
            'ngay_bat_dau_gvcn' => now()->addHours(2),
            'ngay_ket_thuc_gvcn' => now()->addHours(3),
            'ngay_cong_bo' => now()->addHours(4),
        ]);
        $form = $this->form($data, $period, PhieuDanhGia::STATUS_DRAFT);

        $this->actingAs($data['studentUser'])
            ->get(route('sinh-vien.evaluations.index'))
            ->assertOk()
            ->assertSee('Đợt đánh giá chưa mở')
            ->assertSee('14/07/2026 17:00');

        $this->assertFalse(app(DiemRenLuyenService::class)->canStudentEdit($form));
        $this->travelTo(now()->addHour());
        $this->assertTrue(app(DiemRenLuyenService::class)->canStudentEdit($form->refresh()));
    }

    public function test_existing_vietnamese_wall_clock_periods_are_normalized_to_utc(): void
    {
        $data = $this->baseData();
        $period = $this->period($data, [
            'ngay_bat_dau_sinh_vien' => '2026-07-14 16:00:00',
            'ngay_ket_thuc_sinh_vien' => '2026-07-14 17:00:00',
            'ngay_bat_dau_gvcn' => '2026-07-14 17:00:00',
            'ngay_ket_thuc_gvcn' => '2026-07-14 18:00:00',
            'ngay_cong_bo' => null,
        ]);

        $migration = require database_path('migrations/2026_07_14_000001_normalize_evaluation_period_times_to_utc.php');
        $migration->up();

        $raw = \DB::table('dot_danh_gias')->where('id', $period->id)->first();
        $this->assertSame('2026-07-14 09:00:00', $raw->ngay_bat_dau_sinh_vien);
        $this->assertSame('2026-07-14 11:00:00', $raw->ngay_cong_bo);
        $this->assertSame('14/07/2026 16:00', $period->refresh()->displayDate($period->ngay_bat_dau_sinh_vien));
    }

    public function test_admin_schedule_input_is_interpreted_as_vietnamese_time_and_status_is_automatic(): void
    {
        $data = $this->baseData();

        $createResponse = $this->actingAs($data['admin'])
            ->get(route('admin.dot-danh-gia.create'))
            ->assertOk()
            ->assertDontSee('name="trang_thai"', false);

        $this->assertSame($data['namHoc']->id, $createResponse->viewData('dot')->nam_hoc_id);
        $this->assertSame($data['hocKy']->id, $createResponse->viewData('dot')->hoc_ky_id);
        $createResponse->assertSee('data-active="1"', false);

        $this->actingAs($data['admin'])
            ->post(route('admin.dot-danh-gia.store'), [
                'ten_dot' => 'Lịch giờ Việt Nam',
                'nam_hoc_id' => $data['namHoc']->id,
                'hoc_ky_id' => $data['hocKy']->id,
                'ngay_bat_dau_sinh_vien' => '2026-07-15T16:00',
                'ngay_ket_thuc_sinh_vien' => '2026-07-15T17:00',
                'ngay_bat_dau_gvcn' => '2026-07-15T17:00',
                'ngay_ket_thuc_gvcn' => '2026-07-15T18:00',
                'ngay_cong_bo' => '2026-07-15T19:00',
            ])
            ->assertRedirect(route('admin.dot-danh-gia.index'));

        $this->assertDatabaseHas('dot_danh_gias', [
            'ten_dot' => 'Lịch giờ Việt Nam',
            'ngay_bat_dau_sinh_vien' => '2026-07-15 09:00:00',
            'ngay_cong_bo' => '2026-07-15 12:00:00',
            'trang_thai' => DotDanhGia::STATUS_DRAFT,
        ]);
    }

    private function baseData(): array
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate('sinh_vien', 'web');
        $managePeriods = Permission::findOrCreate('manage_dot_danh_gia', 'web');
        Role::findOrCreate('admin', 'web')->givePermissionTo($managePeriods);

        $studentUser = User::factory()->create(['is_active' => true]);
        $studentUser->assignRole('sinh_vien');
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('admin');
        $khoa = Khoa::create(['ma_khoa' => 'CNTT', 'ten_khoa' => 'CNTT', 'is_active' => true]);
        $lop = Lop::create(['khoa_id' => $khoa->id, 'ma_lop' => 'D21', 'ten_lop' => 'D21', 'is_active' => true]);
        $student = SinhVien::create([
            'user_id' => $studentUser->id,
            'lop_id' => $lop->id,
            'ma_sinh_vien' => 'SV001',
            'ho_ten' => 'Sinh viên',
            'trang_thai' => 'dang_hoc',
        ]);
        $namHoc = NamHoc::create([
            'ten_nam_hoc' => '2026-2027',
            'ngay_bat_dau' => '2026-01-01',
            'ngay_ket_thuc' => '2026-12-31',
            'is_active' => true,
        ]);
        $hocKy = HocKy::create([
            'nam_hoc_id' => $namHoc->id,
            'ten_hoc_ky' => 'Học kỳ 1',
            'thu_tu' => 1,
            'is_active' => true,
        ]);

        return compact('studentUser', 'admin', 'student', 'namHoc', 'hocKy');
    }

    private function period(array $data, array $overrides): DotDanhGia
    {
        return DotDanhGia::create([
            'ten_dot' => 'Đợt tự động',
            'nam_hoc_id' => $data['namHoc']->id,
            'hoc_ky_id' => $data['hocKy']->id,
            'ngay_bat_dau_sinh_vien' => $overrides['ngay_bat_dau_sinh_vien'],
            'ngay_ket_thuc_sinh_vien' => $overrides['ngay_ket_thuc_sinh_vien'],
            'ngay_bat_dau_gvcn' => $overrides['ngay_bat_dau_gvcn'],
            'ngay_ket_thuc_gvcn' => $overrides['ngay_ket_thuc_gvcn'],
            'ngay_cong_bo' => $overrides['ngay_cong_bo'],
            'trang_thai' => $overrides['trang_thai'] ?? DotDanhGia::STATUS_DRAFT,
            'created_by' => $data['admin']->id,
        ]);
    }

    private function form(array $data, DotDanhGia $period, string $status, int $studentSuffix = 0): PhieuDanhGia
    {
        $student = $data['student'];

        if ($studentSuffix) {
            $user = User::factory()->create(['is_active' => true]);
            $student = SinhVien::create([
                'user_id' => $user->id,
                'lop_id' => $data['student']->lop_id,
                'ma_sinh_vien' => 'SV'.$studentSuffix,
                'ho_ten' => 'Sinh viên '.$studentSuffix,
                'trang_thai' => 'dang_hoc',
            ]);
        }

        return PhieuDanhGia::create([
            'sinh_vien_id' => $student->id,
            'hoc_ky_id' => $data['hocKy']->id,
            'dot_danh_gia_id' => $period->id,
            'trang_thai' => $status,
        ]);
    }
}
