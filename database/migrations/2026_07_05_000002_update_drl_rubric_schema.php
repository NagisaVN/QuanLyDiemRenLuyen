<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('muc_tieu_chis', 'ma_muc')) {
            Schema::table('muc_tieu_chis', function (Blueprint $table) {
                $table->string('ma_muc')->nullable()->after('tieu_chi_id');
            });
        }

        if (! Schema::hasColumn('muc_tieu_chis', 'loai')) {
            Schema::table('muc_tieu_chis', function (Blueprint $table) {
                $table->string('loai')->default('item')->after('mo_ta');
            });
        }

        Schema::table('muc_tieu_chis', function (Blueprint $table) {
            $table->integer('diem_toi_da')->nullable()->default(null)->change();
        });

        if (! Schema::hasIndex('muc_tieu_chis', 'muc_tieu_chis_ma_muc_unique')) {
            Schema::table('muc_tieu_chis', function (Blueprint $table) {
                $table->unique('ma_muc', 'muc_tieu_chis_ma_muc_unique');
            });
        }

        if (Schema::hasIndex('chi_tiet_danh_gias', 'chi_tiet_danh_gias_phieu_danh_gia_id_tieu_chi_id_unique')) {
            Schema::table('chi_tiet_danh_gias', function (Blueprint $table) {
                $table->dropUnique('chi_tiet_danh_gias_phieu_danh_gia_id_tieu_chi_id_unique');
            });
        }

        if (! Schema::hasColumn('chi_tiet_danh_gias', 'ghi_chu_sinh_vien')) {
            Schema::table('chi_tiet_danh_gias', function (Blueprint $table) {
                $table->text('ghi_chu_sinh_vien')->nullable()->after('ghi_chu');
            });
        }

        if (! Schema::hasColumn('chi_tiet_danh_gias', 'ghi_chu_gvcn')) {
            Schema::table('chi_tiet_danh_gias', function (Blueprint $table) {
                $table->text('ghi_chu_gvcn')->nullable()->after('ghi_chu_sinh_vien');
            });
        }

        if (! Schema::hasColumn('chi_tiet_danh_gias', 'ghi_chu_hoi_dong')) {
            Schema::table('chi_tiet_danh_gias', function (Blueprint $table) {
                $table->text('ghi_chu_hoi_dong')->nullable()->after('ghi_chu_gvcn');
            });
        }

        if (! Schema::hasIndex('chi_tiet_danh_gias', 'chi_tiet_danh_gias_phieu_muc_unique')) {
            Schema::table('chi_tiet_danh_gias', function (Blueprint $table) {
                $table->unique(['phieu_danh_gia_id', 'muc_tieu_chi_id'], 'chi_tiet_danh_gias_phieu_muc_unique');
            });
        }

        if (! Schema::hasColumn('minh_chungs', 'muc_tieu_chi_id')) {
            Schema::table('minh_chungs', function (Blueprint $table) {
                $table->foreignId('muc_tieu_chi_id')
                    ->nullable()
                    ->after('tieu_chi_id')
                    ->constrained('muc_tieu_chis')
                    ->nullOnDelete();
            });
        } elseif (! Schema::hasForeignKey('minh_chungs', ['muc_tieu_chi_id'])) {
            Schema::table('minh_chungs', function (Blueprint $table) {
                $table->foreign('muc_tieu_chi_id')
                    ->references('id')
                    ->on('muc_tieu_chis')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasForeignKey('minh_chungs', ['muc_tieu_chi_id'])) {
            Schema::table('minh_chungs', function (Blueprint $table) {
                $table->dropForeign(['muc_tieu_chi_id']);
            });
        }

        if (Schema::hasColumn('minh_chungs', 'muc_tieu_chi_id')) {
            Schema::table('minh_chungs', function (Blueprint $table) {
                $table->dropColumn('muc_tieu_chi_id');
            });
        }

        if (Schema::hasIndex('chi_tiet_danh_gias', 'chi_tiet_danh_gias_phieu_muc_unique')) {
            Schema::table('chi_tiet_danh_gias', function (Blueprint $table) {
                $table->dropUnique('chi_tiet_danh_gias_phieu_muc_unique');
            });
        }

        $detailColumns = array_values(array_filter(
            ['ghi_chu_sinh_vien', 'ghi_chu_gvcn', 'ghi_chu_hoi_dong'],
            fn (string $column): bool => Schema::hasColumn('chi_tiet_danh_gias', $column),
        ));

        if ($detailColumns !== []) {
            Schema::table('chi_tiet_danh_gias', function (Blueprint $table) use ($detailColumns) {
                $table->dropColumn($detailColumns);
            });
        }

        if (! Schema::hasIndex('chi_tiet_danh_gias', 'chi_tiet_danh_gias_phieu_danh_gia_id_tieu_chi_id_unique')) {
            Schema::table('chi_tiet_danh_gias', function (Blueprint $table) {
                $table->unique(['phieu_danh_gia_id', 'tieu_chi_id'], 'chi_tiet_danh_gias_phieu_danh_gia_id_tieu_chi_id_unique');
            });
        }

        if (Schema::hasIndex('muc_tieu_chis', 'muc_tieu_chis_ma_muc_unique')) {
            Schema::table('muc_tieu_chis', function (Blueprint $table) {
                $table->dropUnique('muc_tieu_chis_ma_muc_unique');
            });
        }

        if (Schema::hasColumn('muc_tieu_chis', 'ma_muc')) {
            Schema::table('muc_tieu_chis', function (Blueprint $table) {
                $table->dropColumn('ma_muc');
            });
        }

        if (Schema::hasColumn('muc_tieu_chis', 'loai')) {
            Schema::table('muc_tieu_chis', function (Blueprint $table) {
                $table->dropColumn('loai');
            });
        }

        Schema::table('muc_tieu_chis', function (Blueprint $table) {
            $table->unsignedTinyInteger('diem_toi_da')->default(0)->change();
        });
    }
};
