<?php

namespace Tests\Feature;

use App\Events\EvaluationStatusChanged;
use App\Models\DotDanhGia;
use App\Models\HocKy;
use App\Models\Khoa;
use App\Models\Lop;
use App\Models\MinhChung;
use App\Models\MucTieuChi;
use App\Models\NamHoc;
use App\Models\PhieuDanhGia;
use App\Models\SinhVien;
use App\Models\User;
use App\Services\DiemRenLuyenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DrlRubricEvaluationTest extends TestCase
{
    use RefreshDatabase;

    public function test_drl_rubric_scores_each_item_and_clamps_section_totals(): void
    {
        $data = $this->baseData();
        $service = app(DiemRenLuyenService::class);
        $phieu = $service->ensurePhieu($data['student']);
        $scores = [];

        foreach (MucTieuChi::query()->where('loai', 'item')->get() as $item) {
            $scores[$item->id] = (int) $item->diem_toi_da > 0 ? 999 : 0;
        }

        $negative = MucTieuChi::query()->where('ma_muc', 'I.1.e')->firstOrFail();
        $positive = MucTieuChi::query()->where('ma_muc', 'I.1.a')->firstOrFail();
        $scores[$negative->id] = -999;
        $scores[$positive->id] = 999;

        $phieu = $service->saveStudentScores($phieu, $scores, $data['studentUser'], 'Tự đánh giá', [
            $positive->id => 'Đi học đầy đủ',
        ]);
        $rubric = $service->rubricForPhieu($phieu);

        $this->assertSame(100, $phieu->diem_tu_cham);
        $this->assertSame(25, $rubric->firstWhere('criterion.ma_tieu_chi', 'TC02')['totals']['student']);
        $this->assertDatabaseHas('chi_tiet_danh_gias', [
            'phieu_danh_gia_id' => $phieu->id,
            'muc_tieu_chi_id' => $negative->id,
            'diem_tu_cham' => -5,
        ]);
        $this->assertDatabaseHas('chi_tiet_danh_gias', [
            'phieu_danh_gia_id' => $phieu->id,
            'muc_tieu_chi_id' => $positive->id,
            'diem_tu_cham' => 5,
            'ghi_chu_sinh_vien' => 'Đi học đầy đủ',
        ]);
    }

    public function test_student_can_upload_evidence_for_a_specific_rubric_item(): void
    {
        Storage::fake('local');

        $data = $this->baseData();
        $service = app(DiemRenLuyenService::class);
        $service->ensurePhieu($data['student']);
        $item = MucTieuChi::query()->where('ma_muc', 'III.2.a')->firstOrFail();

        $this->actingAs($data['studentUser'])
            ->post(route('sinh-vien.evaluations.upload'), [
                'muc_tieu_chi_id' => $item->id,
                'mo_ta' => 'Giấy chứng nhận tình nguyện',
                'files' => [UploadedFile::fake()->image('minh-chung.jpg')],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('minh_chungs', [
            'sinh_vien_id' => $data['student']->id,
            'tieu_chi_id' => $item->tieu_chi_id,
            'muc_tieu_chi_id' => $item->id,
            'mo_ta' => 'Giấy chứng nhận tình nguyện',
        ]);
        $this->assertSame(1, MinhChung::count());
    }

    public function test_status_changes_dispatch_realtime_events_to_expected_users(): void
    {
        $data = $this->baseData();
        $service = app(DiemRenLuyenService::class);
        $phieu = $service->ensurePhieu($data['student']);

        Event::fake([EvaluationStatusChanged::class]);

        $service->submit($phieu);

        Event::assertDispatched(EvaluationStatusChanged::class, fn (EvaluationStatusChanged $event) => $event->type === 'submitted'
            && $event->userId === $data['studentUser']->id);
        Event::assertDispatched(EvaluationStatusChanged::class, fn (EvaluationStatusChanged $event) => $event->type === 'submitted'
            && $event->userId === $data['gvcnUser']->id);

        $phieu->refresh();
        $service->confirmGvcn($phieu, $data['gvcnUser'], 'Đã duyệt');

        Event::assertDispatched(EvaluationStatusChanged::class, fn (EvaluationStatusChanged $event) => $event->type === 'reviewed'
            && $event->userId === $data['hoiDongUser']->id);

        $phieu->refresh();
        $service->approveFinal($phieu, $data['hoiDongUser'], 'Chốt điểm');

        Event::assertDispatched(EvaluationStatusChanged::class, fn (EvaluationStatusChanged $event) => $event->type === 'approved'
            && $event->userId === $data['studentUser']->id);
    }

    public function test_lock_expired_command_locks_forms_and_broadcasts(): void
    {
        $data = $this->baseData([
            'ngay_bat_dau_sinh_vien' => now()->subDays(5),
            'ngay_ket_thuc_sinh_vien' => now()->subDays(4),
            'ngay_bat_dau_gvcn' => now()->subDays(3),
            'ngay_ket_thuc_gvcn' => now()->subDay(),
        ]);

        $phieu = PhieuDanhGia::create([
            'sinh_vien_id' => $data['student']->id,
            'hoc_ky_id' => $data['hocKy']->id,
            'dot_danh_gia_id' => $data['dot']->id,
            'trang_thai' => PhieuDanhGia::STATUS_SUBMITTED,
            'diem_tu_cham' => 50,
        ]);

        Event::fake([EvaluationStatusChanged::class]);

        $this->artisan('evaluations:lock-expired')
            ->assertSuccessful();

        $this->assertSame(PhieuDanhGia::STATUS_LOCKED, $phieu->refresh()->trang_thai);
        Event::assertDispatched(EvaluationStatusChanged::class, fn (EvaluationStatusChanged $event) => $event->type === 'locked'
            && $event->userId === $data['studentUser']->id);
    }

    private function baseData(array $dotOverrides = []): array
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['sinh_vien', 'gvcn', 'hoi_dong_khoa'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $studentUser = User::factory()->create([
            'name' => 'Nguyễn Sinh Viên',
            'email' => 'student@example.test',
            'ma_dang_nhap' => 'sv001',
            'is_active' => true,
        ]);
        $studentUser->assignRole('sinh_vien');

        $gvcnUser = User::factory()->create([
            'name' => 'Giáo viên chủ nhiệm',
            'email' => 'gvcn@example.test',
            'ma_dang_nhap' => 'gvcn01',
            'is_active' => true,
        ]);
        $gvcnUser->assignRole('gvcn');

        $hoiDongUser = User::factory()->create([
            'name' => 'Hội đồng khoa',
            'email' => 'hoidong@example.test',
            'ma_dang_nhap' => 'hd01',
            'is_active' => true,
        ]);
        $hoiDongUser->assignRole('hoi_dong_khoa');

        $khoa = Khoa::create([
            'ma_khoa' => 'CNTT',
            'ten_khoa' => 'Công nghệ thông tin',
            'is_active' => true,
        ]);
        $lop = Lop::create([
            'khoa_id' => $khoa->id,
            'gvcn_id' => $gvcnUser->id,
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
        $hocKy = HocKy::create([
            'nam_hoc_id' => $namHoc->id,
            'ten_hoc_ky' => 'Học kỳ 1',
            'thu_tu' => 1,
            'is_active' => true,
        ]);
        $dot = DotDanhGia::create([
            'ten_dot' => 'Đợt đánh giá học kỳ 1',
            'nam_hoc_id' => $namHoc->id,
            'hoc_ky_id' => $hocKy->id,
            'ngay_bat_dau_sinh_vien' => $dotOverrides['ngay_bat_dau_sinh_vien'] ?? now()->subDay(),
            'ngay_ket_thuc_sinh_vien' => $dotOverrides['ngay_ket_thuc_sinh_vien'] ?? now()->addDay(),
            'ngay_bat_dau_gvcn' => $dotOverrides['ngay_bat_dau_gvcn'] ?? now()->subHour(),
            'ngay_ket_thuc_gvcn' => $dotOverrides['ngay_ket_thuc_gvcn'] ?? now()->addDay(),
            'ngay_cong_bo' => now()->addWeek(),
            'trang_thai' => DotDanhGia::STATUS_OPEN,
            'created_by' => $hoiDongUser->id,
        ]);

        return compact('studentUser', 'gvcnUser', 'hoiDongUser', 'student', 'namHoc', 'hocKy', 'dot');
    }
}
