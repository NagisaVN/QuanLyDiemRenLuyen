<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chi_tiet_danh_gias', function (Blueprint $table): void {
            $table->tinyInteger('diem_tu_cham')->default(0)->change();
            $table->tinyInteger('diem_gvcn')->nullable()->change();
            $table->tinyInteger('diem_hoi_dong')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('chi_tiet_danh_gias', function (Blueprint $table): void {
            $table->unsignedTinyInteger('diem_tu_cham')->default(0)->change();
            $table->unsignedTinyInteger('diem_gvcn')->nullable()->change();
            $table->unsignedTinyInteger('diem_hoi_dong')->nullable()->change();
        });
    }
};
