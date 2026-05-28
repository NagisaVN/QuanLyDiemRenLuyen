<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('khoas', function (Blueprint $table) {
            $table->id();
            $table->string('ma_khoa')->unique();
            $table->string('ten_khoa');
            $table->text('mo_ta')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('lops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('khoa_id')->constrained('khoas')->cascadeOnDelete();
            $table->foreignId('gvcn_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ma_lop')->unique();
            $table->string('ten_lop');
            $table->string('nien_khoa')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('sinh_viens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('lop_id')->constrained('lops')->cascadeOnDelete();
            $table->string('ma_sinh_vien')->unique();
            $table->string('ho_ten');
            $table->date('ngay_sinh')->nullable();
            $table->string('gioi_tinh')->nullable();
            $table->string('so_dien_thoai')->nullable();
            $table->string('dia_chi')->nullable();
            $table->string('trang_thai')->default('dang_hoc');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('nam_hocs', function (Blueprint $table) {
            $table->id();
            $table->string('ten_nam_hoc')->unique();
            $table->date('ngay_bat_dau')->nullable();
            $table->date('ngay_ket_thuc')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('hoc_kys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nam_hoc_id')->constrained('nam_hocs')->cascadeOnDelete();
            $table->string('ten_hoc_ky');
            $table->unsignedTinyInteger('thu_tu');
            $table->date('ngay_bat_dau')->nullable();
            $table->date('ngay_ket_thuc')->nullable();
            $table->dateTime('han_tu_danh_gia')->nullable();
            $table->dateTime('han_gvcn_duyet')->nullable();
            $table->dateTime('han_hoi_dong_duyet')->nullable();
            $table->dateTime('ngay_cong_bo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['nam_hoc_id', 'thu_tu']);
        });

        Schema::create('tieu_chis', function (Blueprint $table) {
            $table->id();
            $table->string('ma_tieu_chi')->unique();
            $table->string('ten_tieu_chi');
            $table->text('mo_ta')->nullable();
            $table->unsignedTinyInteger('diem_toi_da')->default(20);
            $table->unsignedTinyInteger('thu_tu')->default(1);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('muc_tieu_chis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tieu_chi_id')->constrained('tieu_chis')->cascadeOnDelete();
            $table->string('ten_muc');
            $table->text('mo_ta')->nullable();
            $table->unsignedTinyInteger('diem_toi_da')->default(0);
            $table->unsignedTinyInteger('thu_tu')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('phieu_danh_gias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sinh_vien_id')->constrained('sinh_viens')->cascadeOnDelete();
            $table->foreignId('hoc_ky_id')->constrained('hoc_kys')->cascadeOnDelete();
            $table->string('trang_thai')->default('draft');
            $table->unsignedTinyInteger('diem_tu_cham')->default(0);
            $table->unsignedTinyInteger('diem_gvcn')->nullable();
            $table->unsignedTinyInteger('diem_hoi_dong')->nullable();
            $table->unsignedTinyInteger('diem_cuoi')->nullable();
            $table->string('xep_loai')->nullable();
            $table->text('nhan_xet_sinh_vien')->nullable();
            $table->text('nhan_xet_gvcn')->nullable();
            $table->text('nhan_xet_hoi_dong')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['sinh_vien_id', 'hoc_ky_id']);
        });

        Schema::create('chi_tiet_danh_gias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phieu_danh_gia_id')->constrained('phieu_danh_gias')->cascadeOnDelete();
            $table->foreignId('tieu_chi_id')->constrained('tieu_chis')->cascadeOnDelete();
            $table->foreignId('muc_tieu_chi_id')->nullable()->constrained('muc_tieu_chis')->nullOnDelete();
            $table->unsignedTinyInteger('diem_tu_cham')->default(0);
            $table->unsignedTinyInteger('diem_gvcn')->nullable();
            $table->unsignedTinyInteger('diem_hoi_dong')->nullable();
            $table->text('ghi_chu')->nullable();
            $table->timestamps();
            $table->unique(['phieu_danh_gia_id', 'tieu_chi_id']);
        });

        Schema::create('minh_chungs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sinh_vien_id')->constrained('sinh_viens')->cascadeOnDelete();
            $table->foreignId('phieu_danh_gia_id')->constrained('phieu_danh_gias')->cascadeOnDelete();
            $table->foreignId('tieu_chi_id')->nullable()->constrained('tieu_chis')->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ten_file');
            $table->string('duong_dan');
            $table->string('loai_file');
            $table->unsignedBigInteger('kich_thuoc')->default(0);
            $table->text('mo_ta')->nullable();
            $table->string('trang_thai')->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('ghi_chu_duyet')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('hoat_dongs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('tieu_chi_id')->nullable()->constrained('tieu_chis')->nullOnDelete();
            $table->string('ma_hoat_dong')->unique();
            $table->string('ten_hoat_dong');
            $table->string('loai_hoat_dong');
            $table->text('mo_ta')->nullable();
            $table->string('dia_diem')->nullable();
            $table->dateTime('thoi_gian_bat_dau')->nullable();
            $table->dateTime('thoi_gian_ket_thuc')->nullable();
            $table->unsignedInteger('so_luong_toi_da')->nullable();
            $table->integer('diem_cong')->default(0);
            $table->string('trang_thai')->default('open');
            $table->string('qr_token')->nullable()->unique();
            $table->boolean('auto_cong_diem')->default(true);
            $table->boolean('is_bat_buoc')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('hoat_dong_khoa', function (Blueprint $table) {
            $table->foreignId('hoat_dong_id')->constrained('hoat_dongs')->cascadeOnDelete();
            $table->foreignId('khoa_id')->constrained('khoas')->cascadeOnDelete();
            $table->primary(['hoat_dong_id', 'khoa_id']);
        });

        Schema::create('dang_ky_hoat_dongs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hoat_dong_id')->constrained('hoat_dongs')->cascadeOnDelete();
            $table->foreignId('sinh_vien_id')->constrained('sinh_viens')->cascadeOnDelete();
            $table->string('trang_thai')->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('ghi_chu')->nullable();
            $table->timestamps();
            $table->unique(['hoat_dong_id', 'sinh_vien_id']);
        });

        Schema::create('diem_danh_hoat_dongs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hoat_dong_id')->constrained('hoat_dongs')->cascadeOnDelete();
            $table->foreignId('sinh_vien_id')->constrained('sinh_viens')->cascadeOnDelete();
            $table->foreignId('dang_ky_hoat_dong_id')->nullable()->constrained('dang_ky_hoat_dongs')->nullOnDelete();
            $table->foreignId('checked_in_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('phuong_thuc')->default('qr');
            $table->timestamp('checked_in_at')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('ghi_chu')->nullable();
            $table->timestamps();
            $table->unique(['hoat_dong_id', 'sinh_vien_id']);
        });

        Schema::create('diem_ren_luyens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sinh_vien_id')->constrained('sinh_viens')->cascadeOnDelete();
            $table->foreignId('hoc_ky_id')->constrained('hoc_kys')->cascadeOnDelete();
            $table->foreignId('phieu_danh_gia_id')->nullable()->constrained('phieu_danh_gias')->nullOnDelete();
            $table->unsignedTinyInteger('tong_diem')->default(0);
            $table->integer('diem_hoat_dong')->default(0);
            $table->string('xep_loai')->nullable();
            $table->string('trang_thai')->default('final');
            $table->timestamp('cong_bo_at')->nullable();
            $table->timestamps();
            $table->unique(['sinh_vien_id', 'hoc_ky_id']);
        });

        Schema::create('lich_su_chinh_sua_diems', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phieu_danh_gia_id')->nullable()->constrained('phieu_danh_gias')->cascadeOnDelete();
            $table->foreignId('chi_tiet_danh_gia_id')->nullable()->constrained('chi_tiet_danh_gias')->nullOnDelete();
            $table->foreignId('sinh_vien_id')->nullable()->constrained('sinh_viens')->nullOnDelete();
            $table->foreignId('hoc_ky_id')->nullable()->constrained('hoc_kys')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nguon')->default('phieu_danh_gia');
            $table->integer('diem_cu')->nullable();
            $table->integer('diem_moi')->nullable();
            $table->string('noi_dung')->nullable();
            $table->text('ly_do')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('thong_baos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('hoc_ky_id')->nullable()->constrained('hoc_kys')->nullOnDelete();
            $table->string('tieu_de');
            $table->longText('noi_dung');
            $table->string('loai')->default('general');
            $table->string('doi_tuong')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('het_han_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('hanh_dong');
            $table->string('doi_tuong')->nullable();
            $table->unsignedBigInteger('doi_tuong_id')->nullable();
            $table->text('noi_dung')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->string('file_name');
            $table->string('path');
            $table->string('disk')->default('local');
            $table->unsignedBigInteger('size')->nullable();
            $table->string('status')->default('success');
            $table->text('message')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backups');
        Schema::dropIfExists('logs');
        Schema::dropIfExists('thong_baos');
        Schema::dropIfExists('lich_su_chinh_sua_diems');
        Schema::dropIfExists('diem_ren_luyens');
        Schema::dropIfExists('diem_danh_hoat_dongs');
        Schema::dropIfExists('dang_ky_hoat_dongs');
        Schema::dropIfExists('hoat_dong_khoa');
        Schema::dropIfExists('hoat_dongs');
        Schema::dropIfExists('minh_chungs');
        Schema::dropIfExists('chi_tiet_danh_gias');
        Schema::dropIfExists('phieu_danh_gias');
        Schema::dropIfExists('muc_tieu_chis');
        Schema::dropIfExists('tieu_chis');
        Schema::dropIfExists('hoc_kys');
        Schema::dropIfExists('nam_hocs');
        Schema::dropIfExists('sinh_viens');
        Schema::dropIfExists('lops');
        Schema::dropIfExists('khoas');
    }
};
