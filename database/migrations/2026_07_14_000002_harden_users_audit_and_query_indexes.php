<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->softDeletes()->index();
        });

        Schema::table('dot_danh_gias', function (Blueprint $table): void {
            $table->index(['trang_thai', 'ngay_bat_dau_sinh_vien', 'ngay_ket_thuc_sinh_vien'], 'dot_status_student_window_idx');
            $table->index(['trang_thai', 'ngay_bat_dau_gvcn', 'ngay_ket_thuc_gvcn'], 'dot_status_reviewer_window_idx');
        });

        Schema::table('phieu_danh_gias', function (Blueprint $table): void {
            $table->index(['dot_danh_gia_id', 'trang_thai'], 'phieu_dot_status_idx');
        });

        Schema::table('hoat_dongs', function (Blueprint $table): void {
            $table->index(['user_id', 'trang_thai'], 'hoat_dong_owner_status_idx');
            $table->index(['trang_thai', 'thoi_gian_bat_dau'], 'hoat_dong_status_start_idx');
        });

        Schema::table('dang_ky_hoat_dongs', function (Blueprint $table): void {
            $table->index(['hoat_dong_id', 'trang_thai'], 'dang_ky_activity_status_idx');
        });

        Schema::table('diem_danh_hoat_dongs', function (Blueprint $table): void {
            $table->index(['hoat_dong_id', 'status', 'point_awarded'], 'diem_danh_approval_idx');
        });

        Schema::table('logs', function (Blueprint $table): void {
            $table->index(['user_id', 'created_at'], 'logs_actor_time_idx');
            $table->index(['hanh_dong', 'created_at'], 'logs_action_time_idx');
            $table->index(['doi_tuong', 'doi_tuong_id'], 'logs_subject_idx');
        });
    }

    public function down(): void
    {
        foreach ([
            ['logs', 'user_id', 'logs_user_id_keep_idx'],
            ['hoat_dongs', 'user_id', 'hoat_dongs_user_id_keep_idx'],
            ['dang_ky_hoat_dongs', 'hoat_dong_id', 'dang_ky_hoat_dong_id_keep_idx'],
            ['diem_danh_hoat_dongs', 'hoat_dong_id', 'diem_danh_hoat_dong_id_keep_idx'],
            ['phieu_danh_gias', 'dot_danh_gia_id', 'phieu_dot_id_keep_idx'],
        ] as [$tableName, $column, $index]) {
            if (! Schema::hasIndex($tableName, $index)) {
                Schema::table($tableName, fn (Blueprint $table) => $table->index($column, $index));
            }
        }

        Schema::table('logs', function (Blueprint $table): void {
            if (Schema::hasIndex('logs', 'logs_actor_time_idx')) {
                $table->dropIndex('logs_actor_time_idx');
            }
            if (Schema::hasIndex('logs', 'logs_action_time_idx')) {
                $table->dropIndex('logs_action_time_idx');
            }
            if (Schema::hasIndex('logs', 'logs_subject_idx')) {
                $table->dropIndex('logs_subject_idx');
            }
        });
        if (Schema::hasIndex('diem_danh_hoat_dongs', 'diem_danh_approval_idx')) {
            Schema::table('diem_danh_hoat_dongs', fn (Blueprint $table) => $table->dropIndex('diem_danh_approval_idx'));
        }
        if (Schema::hasIndex('dang_ky_hoat_dongs', 'dang_ky_activity_status_idx')) {
            Schema::table('dang_ky_hoat_dongs', fn (Blueprint $table) => $table->dropIndex('dang_ky_activity_status_idx'));
        }
        Schema::table('hoat_dongs', function (Blueprint $table): void {
            if (Schema::hasIndex('hoat_dongs', 'hoat_dong_owner_status_idx')) {
                $table->dropIndex('hoat_dong_owner_status_idx');
            }
            if (Schema::hasIndex('hoat_dongs', 'hoat_dong_status_start_idx')) {
                $table->dropIndex('hoat_dong_status_start_idx');
            }
        });
        if (Schema::hasIndex('phieu_danh_gias', 'phieu_dot_status_idx')) {
            Schema::table('phieu_danh_gias', fn (Blueprint $table) => $table->dropIndex('phieu_dot_status_idx'));
        }
        Schema::table('dot_danh_gias', function (Blueprint $table): void {
            $table->dropIndex('dot_status_student_window_idx');
            $table->dropIndex('dot_status_reviewer_window_idx');
        });
        Schema::table('users', fn (Blueprint $table) => $table->dropSoftDeletes());
    }
};
