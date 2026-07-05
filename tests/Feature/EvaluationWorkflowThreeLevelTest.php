<?php

namespace Tests\Feature;

use App\Models\DiemRenLuyen;
use App\Models\DotDanhGia;
use App\Models\HocKy;
use App\Models\Khoa;
use App\Models\Lop;
use App\Models\MucTieuChi;
use App\Models\NamHoc;
use App\Models\PhieuDanhGia;
use App\Models\SinhVien;
use App\Models\User;
use App\Services\DiemRenLuyenService;
use App\Support\DrlRubric;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class EvaluationWorkflowThreeLevelTest extends TestCase
{
    use RefreshDatabase;

    public function test_evaluation_moves_once_from_student_to_gvcn_to_ctsv_for_current_period(): void
    {
        $data = $this->baseData();
        $scores = $this->defaultScores();

        $this->actingAs($data['studentUser'])
            ->put(route('sinh-vien.evaluations.update'), [
                'scores' => $scores,
                'notes' => [],
                'nhan_xet_sinh_vien' => 'Tự đánh giá',
                'action' => 'submit',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Đã gửi phiếu, chờ GVCN xác nhận.');

        $phieu = PhieuDanhGia::where('sinh_vien_id', $data['student']->id)
            ->where('dot_danh_gia_id', $data['dot']->id)
            ->firstOrFail();

        $this->assertSame(PhieuDanhGia::STATUS_SUBMITTED, $phieu->trang_thai);
        $this->assertSame($data['dot']->id, $phieu->dot_danh_gia_id);
        $this->assertSame(1, PhieuDanhGia::where('sinh_vien_id', $data['student']->id)->where('dot_danh_gia_id', $data['dot']->id)->count());
        $this->assertFalse(app(DiemRenLuyenService::class)->canStudentEdit($phieu));

        $this->actingAs($data['studentUser'])
            ->from(route('sinh-vien.evaluations.index'))
            ->put(route('sinh-vien.evaluations.update'), [
                'scores' => array_fill_keys(array_keys($scores), 0),
                'notes' => [],
                'action' => 'save',
            ])
            ->assertRedirect(route('sinh-vien.evaluations.index'))
            ->assertSessionHasErrors('phieu');

        $this->assertSame(PhieuDanhGia::STATUS_SUBMITTED, $phieu->refresh()->trang_thai);

        $this->actingAs($data['gvcnUser'])
            ->get(route('gvcn.evaluations.index'))
            ->assertOk()
            ->assertSee('SV001');

        $this->actingAs($data['ctsvUser'])
            ->get(route('hoi-dong.evaluations.index'))
            ->assertOk()
            ->assertDontSee('SV001');

        $this->actingAs($data['gvcnUser'])
            ->from(route('gvcn.evaluations.show', $phieu))
            ->post(route('gvcn.evaluations.confirm', $phieu), [
                'nhan_xet_gvcn' => 'Đã xác nhận',
            ])
            ->assertRedirect(route('gvcn.evaluations.show', $phieu))
            ->assertSessionHas('status', 'Đã xác nhận phiếu, chờ CTSV duyệt cuối.');

        $this->assertSame(PhieuDanhGia::STATUS_REVIEWED, $phieu->refresh()->trang_thai);
        $this->assertSame($data['gvcnUser']->id, $phieu->reviewed_by);
        $this->assertNotNull($phieu->reviewed_at);

        $this->actingAs($data['gvcnUser'])
            ->from(route('gvcn.evaluations.show', $phieu))
            ->post(route('gvcn.evaluations.confirm', $phieu))
            ->assertRedirect(route('gvcn.evaluations.show', $phieu))
            ->assertSessionHasErrors('phieu');

        $this->assertSame(PhieuDanhGia::STATUS_REVIEWED, $phieu->refresh()->trang_thai);

        $this->actingAs($data['ctsvUser'])
            ->get(route('hoi-dong.evaluations.index'))
            ->assertOk()
            ->assertSee('SV001');

        $this->actingAs($data['ctsvUser'])
            ->from(route('hoi-dong.evaluations.show', $phieu))
            ->post(route('hoi-dong.evaluations.approve', $phieu), [
                'nhan_xet_hoi_dong' => 'Chốt điểm',
            ])
            ->assertRedirect(route('hoi-dong.evaluations.show', $phieu))
            ->assertSessionHas('status', 'Đã xác nhận điểm rèn luyện cuối cùng.');

        $this->assertSame(PhieuDanhGia::STATUS_APPROVED, $phieu->refresh()->trang_thai);
        $this->assertSame($data['ctsvUser']->id, $phieu->approved_by);
        $this->assertNotNull($phieu->approved_at);
        $this->assertNotNull($phieu->diem_cuoi);
        $this->assertDatabaseHas('diem_ren_luyens', [
            'sinh_vien_id' => $data['student']->id,
            'hoc_ky_id' => $data['hocKy']->id,
            'phieu_danh_gia_id' => $phieu->id,
            'tong_diem' => $phieu->diem_cuoi,
            'trang_thai' => 'final',
        ]);
        $this->assertSame(1, DiemRenLuyen::where('phieu_danh_gia_id', $phieu->id)->count());

        $this->actingAs($data['ctsvUser'])
            ->from(route('hoi-dong.evaluations.show', $phieu))
            ->post(route('hoi-dong.evaluations.approve', $phieu))
            ->assertRedirect(route('hoi-dong.evaluations.show', $phieu))
            ->assertSessionHasErrors('phieu');

        $this->actingAs($data['studentUser'])
            ->from(route('sinh-vien.evaluations.index'))
            ->put(route('sinh-vien.evaluations.update'), [
                'scores' => $scores,
                'notes' => [],
                'action' => 'save',
            ])
            ->assertRedirect(route('sinh-vien.evaluations.index'))
            ->assertSessionHasErrors('phieu');

        $this->assertSame(PhieuDanhGia::STATUS_APPROVED, $phieu->refresh()->trang_thai);
        $this->assertSame(1, PhieuDanhGia::where('sinh_vien_id', $data['student']->id)->where('dot_danh_gia_id', $data['dot']->id)->count());
    }

    private function baseData(): array
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['sinh_vien', 'gvcn', 'hoi_dong_khoa'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $studentUser = User::factory()->create([
            'name' => 'Nguyễn Sinh Viên',
            'email' => 'sv001@example.test',
            'ma_dang_nhap' => 'sv001',
            'is_active' => true,
        ]);
        $studentUser->assignRole('sinh_vien');

        $gvcnUser = User::factory()->create([
            'name' => 'Giáo viên chủ nhiệm',
            'email' => 'gvcn01@example.test',
            'ma_dang_nhap' => 'gvcn01',
            'is_active' => true,
        ]);
        $gvcnUser->assignRole('gvcn');

        $ctsvUser = User::factory()->create([
            'name' => 'Công tác sinh viên',
            'email' => 'ctsv01@example.test',
            'ma_dang_nhap' => 'ctsv01',
            'is_active' => true,
        ]);
        $ctsvUser->assignRole('hoi_dong_khoa');

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
            'ngay_bat_dau_sinh_vien' => now()->subDay(),
            'ngay_ket_thuc_sinh_vien' => now()->addDay(),
            'ngay_bat_dau_gvcn' => now()->subHour(),
            'ngay_ket_thuc_gvcn' => now()->addDay(),
            'ngay_cong_bo' => now()->addWeek(),
            'trang_thai' => DotDanhGia::STATUS_OPEN,
            'created_by' => $ctsvUser->id,
        ]);

        return compact('studentUser', 'gvcnUser', 'ctsvUser', 'student', 'hocKy', 'dot');
    }

    private function defaultScores(): array
    {
        DrlRubric::syncIfMissing();

        return MucTieuChi::query()
            ->where('loai', MucTieuChi::TYPE_ITEM)
            ->where('is_active', true)
            ->get()
            ->mapWithKeys(function (MucTieuChi $item): array {
                $limit = (int) $item->diem_toi_da;

                return [$item->id => $limit < 0 ? 0 : min(1, $limit)];
            })
            ->all();
    }
}
