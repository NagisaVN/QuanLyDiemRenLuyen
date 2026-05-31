<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dot_danh_gias', function (Blueprint $table) {
            $table->id();
            $table->string('ten_dot');
            $table->foreignId('nam_hoc_id')->constrained('nam_hocs')->cascadeOnDelete();
            $table->foreignId('hoc_ky_id')->constrained('hoc_kys')->cascadeOnDelete();
            $table->dateTime('ngay_bat_dau_sinh_vien');
            $table->dateTime('ngay_ket_thuc_sinh_vien');
            $table->dateTime('ngay_bat_dau_gvcn');
            $table->dateTime('ngay_ket_thuc_gvcn');
            $table->dateTime('ngay_cong_bo')->nullable();
            $table->enum('trang_thai', ['draft', 'open', 'closed', 'published'])->default('draft');
            $table->text('mo_ta')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('phieu_danh_gias', function (Blueprint $table) {
            $table->foreignId('dot_danh_gia_id')
                ->nullable()
                ->after('hoc_ky_id')
                ->constrained('dot_danh_gias')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('phieu_danh_gias', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dot_danh_gia_id');
        });

        Schema::dropIfExists('dot_danh_gias');
    }
};
