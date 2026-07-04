<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('phieu_danh_gias', function (Blueprint $table) {
            $table->index('sinh_vien_id', 'phieu_danh_gias_sinh_vien_id_keep_index');
            $table->index('hoc_ky_id', 'phieu_danh_gias_hoc_ky_id_keep_index');
            $table->dropUnique('phieu_danh_gias_sinh_vien_id_hoc_ky_id_unique');
            $table->unique(['sinh_vien_id', 'dot_danh_gia_id'], 'phieu_danh_gias_sinh_vien_dot_unique');
        });

        Schema::table('diem_ren_luyens', function (Blueprint $table) {
            $table->index('sinh_vien_id', 'diem_ren_luyens_sinh_vien_id_keep_index');
            $table->index('hoc_ky_id', 'diem_ren_luyens_hoc_ky_id_keep_index');
            $table->dropUnique('diem_ren_luyens_sinh_vien_id_hoc_ky_id_unique');
            $table->unique('phieu_danh_gia_id', 'diem_ren_luyens_phieu_unique');
        });
    }

    public function down(): void
    {
        Schema::table('diem_ren_luyens', function (Blueprint $table) {
            $table->dropUnique('diem_ren_luyens_phieu_unique');
            $table->unique(['sinh_vien_id', 'hoc_ky_id'], 'diem_ren_luyens_sinh_vien_id_hoc_ky_id_unique');
        });

        Schema::table('phieu_danh_gias', function (Blueprint $table) {
            $table->dropUnique('phieu_danh_gias_sinh_vien_dot_unique');
            $table->unique(['sinh_vien_id', 'hoc_ky_id'], 'phieu_danh_gias_sinh_vien_id_hoc_ky_id_unique');
        });
    }
};
