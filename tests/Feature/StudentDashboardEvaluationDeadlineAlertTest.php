<?php

namespace Tests\Feature;

use App\Models\DotDanhGia;
use App\Models\HocKy;
use App\Models\Khoa;
use App\Models\Lop;
use App\Models\NamHoc;
use App\Models\PhieuDanhGia;
use App\Models\SinhVien;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class StudentDashboardEvaluationDeadlineAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_alert_is_shown_on_every_dashboard_visit_when_deadline_is_exactly_seven_days_away(): void
    {
        $this->travelTo(Carbon::parse('2026-07-15 08:00:00'));
        $data = $this->baseData();
        $period = $this->period($data, now()->addDays(7));

        foreach (range(1, 2) as $visit) {
            $response = $this->actingAs($data['student']->user)
                ->get(route('sinh-vien.dashboard'));

            $response->assertOk()
                ->assertSee('data-testid="evaluation-deadline-alert"', false)
                ->assertSee('Đợt đánh giá sắp đóng')
                ->assertSee($period->ten_dot)
                ->assertSee($period->displayDate($period->ngay_ket_thuc_sinh_vien))
                ->assertSee(route('sinh-vien.evaluations.index'), false);
        }
    }

    public function test_alert_is_shown_when_deadline_is_less_than_seven_days_and_form_is_draft(): void
    {
        $this->travelTo(Carbon::parse('2026-07-15 08:00:00'));
        $data = $this->baseData();
        $period = $this->period($data, now()->addDays(2));
        $this->form($data, $period, PhieuDanhGia::STATUS_DRAFT);

        $this->actingAs($data['student']->user)
            ->get(route('sinh-vien.dashboard'))
            ->assertOk()
            ->assertSee('data-testid="evaluation-deadline-alert"', false);
    }

    public function test_alert_is_not_shown_outside_the_last_seven_days(): void
    {
        $this->travelTo(Carbon::parse('2026-07-15 08:00:00'));
        $data = $this->baseData();
        $this->period($data, now()->addDays(7)->addSecond());

        $this->actingAs($data['student']->user)
            ->get(route('sinh-vien.dashboard'))
            ->assertOk()
            ->assertDontSee('data-testid="evaluation-deadline-alert"', false);
    }

    public function test_alert_is_not_shown_after_the_period_has_closed(): void
    {
        $this->travelTo(Carbon::parse('2026-07-15 08:00:00'));
        $data = $this->baseData();
        $this->period($data, now()->subSecond());

        $this->actingAs($data['student']->user)
            ->get(route('sinh-vien.dashboard'))
            ->assertOk()
            ->assertDontSee('data-testid="evaluation-deadline-alert"', false);
    }

    #[DataProvider('completedStatuses')]
    public function test_alert_is_not_shown_for_a_completed_form(string $status): void
    {
        $this->travelTo(Carbon::parse('2026-07-15 08:00:00'));
        $data = $this->baseData();
        $period = $this->period($data, now()->addDay());
        $this->form($data, $period, $status);

        $this->actingAs($data['student']->user)
            ->get(route('sinh-vien.dashboard'))
            ->assertOk()
            ->assertDontSee('data-testid="evaluation-deadline-alert"', false);
    }

    public static function completedStatuses(): array
    {
        return [
            'submitted' => [PhieuDanhGia::STATUS_SUBMITTED],
            'reviewed' => [PhieuDanhGia::STATUS_REVIEWED],
            'approved' => [PhieuDanhGia::STATUS_APPROVED],
            'locked' => [PhieuDanhGia::STATUS_LOCKED],
        ];
    }

    private function baseData(): array
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permission = Permission::findOrCreate('self evaluate', 'web');
        $role = Role::findOrCreate('sinh_vien', 'web');
        $role->givePermissionTo($permission);

        $admin = User::factory()->create(['is_active' => true]);
        $studentUser = User::factory()->create(['is_active' => true]);
        $studentUser->assignRole($role);

        $khoa = Khoa::create(['ma_khoa' => 'CNTT', 'ten_khoa' => 'Công nghệ thông tin', 'is_active' => true]);
        $lop = Lop::create(['khoa_id' => $khoa->id, 'ma_lop' => 'D21', 'ten_lop' => 'D21', 'is_active' => true]);
        $namHoc = NamHoc::create(['ten_nam_hoc' => '2026-2027', 'is_active' => true]);
        $hocKy = HocKy::create([
            'nam_hoc_id' => $namHoc->id,
            'ten_hoc_ky' => 'Học kỳ 1',
            'thu_tu' => 1,
            'is_active' => true,
        ]);
        $student = SinhVien::create([
            'user_id' => $studentUser->id,
            'lop_id' => $lop->id,
            'ma_sinh_vien' => 'SV001',
            'ho_ten' => 'Sinh viên thử nghiệm',
            'trang_thai' => 'dang_hoc',
        ]);

        return compact('admin', 'student', 'namHoc', 'hocKy');
    }

    private function period(array $data, Carbon $deadline): DotDanhGia
    {
        return DotDanhGia::create([
            'ten_dot' => 'Đợt đánh giá học kỳ 1 năm học 2026-2027',
            'nam_hoc_id' => $data['namHoc']->id,
            'hoc_ky_id' => $data['hocKy']->id,
            'ngay_bat_dau_sinh_vien' => now()->subDay(),
            'ngay_ket_thuc_sinh_vien' => $deadline,
            'ngay_bat_dau_gvcn' => $deadline,
            'ngay_ket_thuc_gvcn' => $deadline->copy()->addDay(),
            'ngay_cong_bo' => $deadline->copy()->addDays(2),
            'trang_thai' => DotDanhGia::STATUS_OPEN,
            'created_by' => $data['admin']->id,
        ]);
    }

    private function form(array $data, DotDanhGia $period, string $status): PhieuDanhGia
    {
        return PhieuDanhGia::create([
            'sinh_vien_id' => $data['student']->id,
            'hoc_ky_id' => $data['hocKy']->id,
            'dot_danh_gia_id' => $period->id,
            'trang_thai' => $status,
        ]);
    }
}
