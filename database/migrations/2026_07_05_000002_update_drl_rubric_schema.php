<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('muc_tieu_chis', function (Blueprint $table) {
            $table->string('ma_muc')->nullable()->after('tieu_chi_id');
            $table->string('loai')->default('item')->after('mo_ta');
            $table->integer('diem_toi_da')->nullable()->default(null)->change();
            $table->unique('ma_muc', 'muc_tieu_chis_ma_muc_unique');
        });

        Schema::table('chi_tiet_danh_gias', function (Blueprint $table) {
            $table->dropUnique('chi_tiet_danh_gias_phieu_danh_gia_id_tieu_chi_id_unique');
            $table->text('ghi_chu_sinh_vien')->nullable()->after('ghi_chu');
            $table->text('ghi_chu_gvcn')->nullable()->after('ghi_chu_sinh_vien');
            $table->text('ghi_chu_hoi_dong')->nullable()->after('ghi_chu_gvcn');
            $table->unique(['phieu_danh_gia_id', 'muc_tieu_chi_id'], 'chi_tiet_danh_gias_phieu_muc_unique');
        });

        Schema::table('minh_chungs', function (Blueprint $table) {
            $table->foreignId('muc_tieu_chi_id')
                ->nullable()
                ->after('tieu_chi_id')
                ->constrained('muc_tieu_chis')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('minh_chungs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('muc_tieu_chi_id');
        });

        Schema::table('chi_tiet_danh_gias', function (Blueprint $table) {
            $table->dropUnique('chi_tiet_danh_gias_phieu_muc_unique');
            $table->dropColumn(['ghi_chu_sinh_vien', 'ghi_chu_gvcn', 'ghi_chu_hoi_dong']);
            $table->unique(['phieu_danh_gia_id', 'tieu_chi_id'], 'chi_tiet_danh_gias_phieu_danh_gia_id_tieu_chi_id_unique');
        });

        Schema::table('muc_tieu_chis', function (Blueprint $table) {
            $table->dropUnique('muc_tieu_chis_ma_muc_unique');
            $table->dropColumn(['ma_muc', 'loai']);
            $table->unsignedTinyInteger('diem_toi_da')->default(0)->change();
        });
    }
};
