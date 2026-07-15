<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dot_danh_gias', function (Blueprint $table): void {
            $table->boolean('is_system_sample')->default(false)->after('mo_ta');
        });

        DB::table('dot_danh_gias')
            ->where('ten_dot', 'Đợt đánh giá học kỳ 3 năm học 2025-2026')
            ->where('mo_ta', 'Đợt mẫu để sinh viên tự đánh giá và GVCN duyệt theo thời hạn.')
            ->update(['is_system_sample' => true]);
    }

    public function down(): void
    {
        Schema::table('dot_danh_gias', function (Blueprint $table): void {
            $table->dropColumn('is_system_sample');
        });
    }
};
