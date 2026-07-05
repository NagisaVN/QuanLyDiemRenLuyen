<?php

namespace Tests\Feature;

use App\Exports\DiemRenLuyenExport;
use App\Models\DiemRenLuyen;
use App\Models\DotDanhGia;
use App\Models\HocKy;
use App\Models\Khoa;
use App\Models\Lop;
use App\Models\NamHoc;
use App\Models\PhieuDanhGia;
use App\Models\SinhVien;
use App\Models\TieuChi;
use App\Models\User;
use App\Services\DiemRenLuyenService;
use App\Services\DotDanhGiaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class EvaluationPeriodCurrentTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_periods_ignore_published_periods_and_teacher_can_use_closed_review_window(): void
    {
        $this->travelTo(Carbon::parse('2026-07-04 10:00:00'));
        $data = $this->baseData();
        $periods = app(DotDanhGiaService::class);

        $this->createDot($data['hk1'], $data['admin'], DotDanhGia::STATUS_PUBLISHED, [
            'ten_dot' => 'HK1 đã công bố',
            'ngay_bat_dau_sinh_vien' => now()->subDays(5),
            'ngay_bat_dau_gvcn' => now()->subDays(4),
            'ngay_cong_bo' => now(),
        ]);
        $this->createDot($data['hk2'], $data['admin'], DotDanhGia::STATUS_CLOSED, [
            'ten_dot' => 'HK2 đã đóng',
            'ngay_bat_dau_sinh_vien' => now()->subHours(3),
            'ngay_bat_dau_gvcn' => now()->subHours(2),
        ]);
        $open = $this->createDot($data['hk2'], $data['admin'], DotDanhGia::STATUS_OPEN, [
            'ten_dot' => 'HK2 đang mở',
            'ngay_bat_dau_sinh_vien' => now()->subHour(),
            'ngay_bat_dau_gvcn' => now()->subMinutes(30),
        ]);

        $this->assertTrue($open->is($periods->getCurrentStudentPeriod()));
        $this->assertTrue($open->is($periods->getCurrentTeacherPeriod()));

        $open->update(['trang_thai' => DotDanhGia::STATUS_CLOSED]);

        $this->assertNull($periods->getCurrentStudentPeriod());
        $this->assertTrue($open->refresh()->is($periods->getCurrentTeacherPeriod()));
    }

    public function test_student_moves_from_published_hk1_to_open_hk2_without_overwriting_old_form(): void
    {
        $this->travelTo(Carbon::parse('2026-07-04 10:00:00'));
        $data = $this->baseData();
        $periods = app(DotDanhGiaService::class);
        $scores = [$data['criterion']->id => 18];
        $diem = app(DiemRenLuyenService::class);

        $hk1Dot = $this->createDot($data['hk1'], $data['admin'], DotDanhGia::STATUS_OPEN, [
            'ten_dot' => 'HK1 đang mở',
        ]);
        $hk1Form = $diem->ensurePhieu($data['student']);
        $diem->saveStudentScores($hk1Form, $scores, $data['studentUser']);

        $this->assertSame($hk1Dot->id, $hk1Form->dot_danh_gia_id);
        $this->assertSame($data['hk1']->id, $hk1Form->hoc_ky_id);

        $periods->close($hk1Dot, $data['admin']);
        $periods->publish($hk1Dot->refresh(), $data['admin']);
        $hk1Form->refresh();

        $this->assertSame(DotDanhGia::STATUS_PUBLISHED, $hk1Dot->refresh()->trang_thai);
        $this->assertSame(PhieuDanhGia::STATUS_LOCKED, $hk1Form->trang_thai);
        $this->assertNotNull($hk1Form->locked_at);
        $this->assertFalse($diem->canStudentEdit($hk1Form));

        $hk2Dot = $this->createDot($data['hk2'], $data['admin'], DotDanhGia::STATUS_OPEN, [
            'ten_dot' => 'HK2 đang mở',
            'ngay_bat_dau_sinh_vien' => now()->addMinute(),
            'ngay_bat_dau_gvcn' => now()->addMinutes(2),
        ]);
        $this->travelTo(now()->addMinutes(3));

        $hk2Form = $diem->ensurePhieu($data['student']);

        $this->assertSame($hk2Dot->id, $hk2Form->dot_danh_gia_id);
        $this->assertSame($data['hk2']->id, $hk2Form->hoc_ky_id);
        $this->assertNotSame($hk1Form->id, $hk2Form->id);
        $this->assertSame(2, PhieuDanhGia::where('sinh_vien_id', $data['student']->id)->count());
        $this->assertDatabaseHas('phieu_danh_gias', [
            'id' => $hk1Form->id,
            'dot_danh_gia_id' => $hk1Dot->id,
            'hoc_ky_id' => $data['hk1']->id,
        ]);
    }

    public function test_student_can_evaluate_new_period_in_same_semester_after_previous_period_was_published(): void
    {
        $this->travelTo(Carbon::parse('2026-07-04 10:00:00'));
        $data = $this->baseData();
        $periods = app(DotDanhGiaService::class);
        $diem = app(DiemRenLuyenService::class);

        $oldDot = $this->createDot($data['hk2'], $data['admin'], DotDanhGia::STATUS_OPEN, [
            'ten_dot' => 'Đợt cũ học kỳ 2',
        ]);
        $oldForm = $diem->ensurePhieu($data['student']);
        $diem->submit($oldForm);
        $diem->confirmGvcn($oldForm->refresh(), $data['admin']);
        $diem->approveFinal($oldForm, $data['admin']);

        $periods->close($oldDot, $data['admin']);
        $periods->publish($oldDot->refresh(), $data['admin']);

        $newDot = $this->createDot($data['hk2'], $data['admin'], DotDanhGia::STATUS_OPEN, [
            'ten_dot' => 'Đợt mới học kỳ 2',
            'ngay_bat_dau_sinh_vien' => now()->addMinute(),
            'ngay_bat_dau_gvcn' => now()->addMinutes(2),
        ]);
        $this->travelTo(now()->addMinutes(3));

        $newForm = $diem->ensurePhieu($data['student']);
        $diem->submit($newForm);
        $diem->confirmGvcn($newForm->refresh(), $data['admin']);
        $diem->approveFinal($newForm, $data['admin']);

        $this->assertSame($data['hk2']->id, $newForm->hoc_ky_id);
        $this->assertSame($newDot->id, $newForm->dot_danh_gia_id);
        $this->assertNotSame($oldForm->id, $newForm->id);
        $this->assertSame(2, PhieuDanhGia::where('sinh_vien_id', $data['student']->id)->where('hoc_ky_id', $data['hk2']->id)->count());
        $this->assertSame(2, DiemRenLuyen::where('sinh_vien_id', $data['student']->id)->where('hoc_ky_id', $data['hk2']->id)->count());
        $this->assertDatabaseHas('phieu_danh_gias', [
            'id' => $oldForm->id,
            'dot_danh_gia_id' => $oldDot->id,
            'hoc_ky_id' => $data['hk2']->id,
        ]);
        $this->assertDatabaseHas('phieu_danh_gias', [
            'id' => $newForm->id,
            'dot_danh_gia_id' => $newDot->id,
            'hoc_ky_id' => $data['hk2']->id,
        ]);
    }

    public function test_published_period_ui_only_shows_result_actions_and_draft_can_be_deleted(): void
    {
        $this->travelTo(Carbon::parse('2026-07-04 10:00:00'));
        $data = $this->baseData(withPermissions: true);

        $published = $this->createDot($data['hk1'], $data['admin'], DotDanhGia::STATUS_PUBLISHED, [
            'ten_dot' => 'HK1 đã công bố',
            'ngay_cong_bo' => now(),
        ]);

        $this->actingAs($data['admin'])
            ->get(route('admin.dot-danh-gia.index'))
            ->assertOk()
            ->assertSee('Xem kết quả')
            ->assertSee('Xuất Excel')
            ->assertDontSee('Mở lại')
            ->assertDontSee('Đóng</button>', false)
            ->assertDontSee('Sửa</a>', false)
            ->assertDontSee('Xóa</button>', false);

        $draft = $this->createDot($data['hk2'], $data['admin'], DotDanhGia::STATUS_DRAFT, [
            'ten_dot' => 'HK2 nháp',
        ]);

        $this->actingAs($data['admin'])
            ->delete(route('admin.dot-danh-gia.destroy', $draft))
            ->assertRedirect();

        $this->assertDatabaseMissing('dot_danh_gias', ['id' => $draft->id]);
        $this->assertDatabaseHas('dot_danh_gias', ['id' => $published->id]);
    }

    public function test_published_period_export_is_filtered_by_dot_danh_gia_id(): void
    {
        $this->travelTo(Carbon::parse('2026-07-04 10:00:00'));
        $data = $this->baseData(withPermissions: true);

        $dot1 = $this->createDot($data['hk1'], $data['admin'], DotDanhGia::STATUS_PUBLISHED, [
            'ten_dot' => 'HK1 đã công bố',
            'ngay_cong_bo' => now(),
        ]);
        $dot2 = $this->createDot($data['hk2'], $data['admin'], DotDanhGia::STATUS_PUBLISHED, [
            'ten_dot' => 'HK2 đã công bố',
            'ngay_cong_bo' => now()->addMinute(),
        ]);
        $form1 = $this->createFinalResult($data['student'], $data['hk1'], $dot1, 82);
        $this->createFinalResult($data['student'], $data['hk2'], $dot2, 91);

        Excel::fake();

        $this->actingAs($data['admin'])
            ->get(route('admin.dot-danh-gia.export', $dot1))
            ->assertOk();

        Excel::assertDownloaded("ket-qua-dot-{$dot1->id}.xlsx", function (DiemRenLuyenExport $export) use ($form1) {
            $rows = $export->collection();

            return $rows->count() === 1
                && $rows->first()->phieu_danh_gia_id === $form1->id;
        });
    }

    private function baseData(bool $withPermissions = false): array
    {
        if ($withPermissions) {
            $this->createAdminRole();
        }

        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'ma_dang_nhap' => 'admin',
            'is_active' => true,
        ]);

        if ($withPermissions) {
            $admin->assignRole('admin');
        }

        $studentUser = User::factory()->create([
            'name' => 'Sinh viên',
            'email' => 'student@example.test',
            'ma_dang_nhap' => 'sv001',
            'is_active' => true,
        ]);
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
        $student = SinhVien::create([
            'user_id' => $studentUser->id,
            'lop_id' => $lop->id,
            'ma_sinh_vien' => 'SV001',
            'ho_ten' => 'Nguyễn Sinh Viên',
            'trang_thai' => 'dang_hoc',
        ]);
        $namHoc = NamHoc::create([
            'ten_nam_hoc' => '2026-2027',
            'ngay_bat_dau' => '2026-01-01',
            'ngay_ket_thuc' => '2026-12-31',
            'is_active' => true,
        ]);
        $hk1 = HocKy::create([
            'nam_hoc_id' => $namHoc->id,
            'ten_hoc_ky' => 'Học kỳ 1',
            'thu_tu' => 1,
            'is_active' => false,
        ]);
        $hk2 = HocKy::create([
            'nam_hoc_id' => $namHoc->id,
            'ten_hoc_ky' => 'Học kỳ 2',
            'thu_tu' => 2,
            'is_active' => true,
        ]);
        $criterion = TieuChi::create([
            'ma_tieu_chi' => 'TC01',
            'ten_tieu_chi' => 'Ý thức học tập',
            'diem_toi_da' => 20,
            'thu_tu' => 1,
            'is_active' => true,
        ]);

        return compact('admin', 'studentUser', 'student', 'namHoc', 'hk1', 'hk2', 'criterion');
    }

    private function createAdminRole(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'manage_dot_danh_gia',
            'open_dot_danh_gia',
            'close_dot_danh_gia',
            'publish_dot_danh_gia',
            'export reports',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
        }

        Role::create(['name' => 'admin', 'guard_name' => 'web'])
            ->givePermissionTo($permissions);
    }

    private function createDot(HocKy $hocKy, User $admin, string $status, array $overrides = []): DotDanhGia
    {
        return DotDanhGia::create([
            'ten_dot' => $overrides['ten_dot'] ?? "Đợt {$hocKy->ten_hoc_ky} {$status}",
            'nam_hoc_id' => $hocKy->nam_hoc_id,
            'hoc_ky_id' => $hocKy->id,
            'ngay_bat_dau_sinh_vien' => $overrides['ngay_bat_dau_sinh_vien'] ?? now()->subDay(),
            'ngay_ket_thuc_sinh_vien' => $overrides['ngay_ket_thuc_sinh_vien'] ?? now()->addDay(),
            'ngay_bat_dau_gvcn' => $overrides['ngay_bat_dau_gvcn'] ?? now()->subHour(),
            'ngay_ket_thuc_gvcn' => $overrides['ngay_ket_thuc_gvcn'] ?? now()->addDays(2),
            'ngay_cong_bo' => $overrides['ngay_cong_bo'] ?? null,
            'trang_thai' => $status,
            'created_by' => $admin->id,
            'updated_by' => $overrides['updated_by'] ?? null,
            'mo_ta' => $overrides['mo_ta'] ?? null,
        ]);
    }

    private function createFinalResult(SinhVien $student, HocKy $hocKy, DotDanhGia $dot, int $score): PhieuDanhGia
    {
        $form = PhieuDanhGia::create([
            'sinh_vien_id' => $student->id,
            'hoc_ky_id' => $hocKy->id,
            'dot_danh_gia_id' => $dot->id,
            'trang_thai' => PhieuDanhGia::STATUS_APPROVED,
            'diem_tu_cham' => $score,
            'diem_gvcn' => $score,
            'diem_hoi_dong' => $score,
            'diem_cuoi' => $score,
            'xep_loai' => 'Tốt',
        ]);

        DiemRenLuyen::create([
            'sinh_vien_id' => $student->id,
            'hoc_ky_id' => $hocKy->id,
            'phieu_danh_gia_id' => $form->id,
            'tong_diem' => $score,
            'diem_hoat_dong' => 0,
            'xep_loai' => 'Tốt',
            'trang_thai' => 'final',
            'cong_bo_at' => $dot->ngay_cong_bo,
        ]);

        return $form;
    }
}
