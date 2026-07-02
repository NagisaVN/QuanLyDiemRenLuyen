<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hoat_dongs', function (Blueprint $table) {
            $table->decimal('location_lat', 10, 7)->nullable()->after('dia_diem');
            $table->decimal('location_lng', 10, 7)->nullable()->after('location_lat');
            $table->unsignedInteger('location_radius_meters')->default(100)->after('location_lng');
        });

        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hoat_dong_id')->constrained('hoat_dongs')->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('token', 96)->unique();
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['hoat_dong_id', 'type', 'is_active']);
            $table->index(['start_at', 'end_at']);
        });

        Schema::table('diem_danh_hoat_dongs', function (Blueprint $table) {
            $table->foreignId('check_in_session_id')->nullable()->after('dang_ky_hoat_dong_id')->constrained('attendance_sessions')->nullOnDelete();
            $table->decimal('check_in_lat', 10, 7)->nullable()->after('checked_in_at');
            $table->decimal('check_in_lng', 10, 7)->nullable()->after('check_in_lat');
            $table->unsignedInteger('check_in_distance_meters')->nullable()->after('check_in_lng');
            $table->foreignId('check_out_session_id')->nullable()->after('check_in_distance_meters')->constrained('attendance_sessions')->nullOnDelete();
            $table->timestamp('check_out_time')->nullable()->after('check_out_session_id');
            $table->decimal('check_out_lat', 10, 7)->nullable()->after('check_out_time');
            $table->decimal('check_out_lng', 10, 7)->nullable()->after('check_out_lat');
            $table->unsignedInteger('check_out_distance_meters')->nullable()->after('check_out_lng');
            $table->string('status', 30)->default('not_completed')->after('check_out_distance_meters');
            $table->boolean('point_awarded')->default(false)->after('status');
        });

        Schema::create('conduct_point_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sinh_vien_id')->constrained('sinh_viens')->cascadeOnDelete();
            $table->foreignId('hoat_dong_id')->constrained('hoat_dongs')->cascadeOnDelete();
            $table->integer('point');
            $table->string('type')->default('activity');
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->unique(['sinh_vien_id', 'hoat_dong_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conduct_point_logs');

        Schema::table('diem_danh_hoat_dongs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('check_in_session_id');
            $table->dropConstrainedForeignId('check_out_session_id');
            $table->dropColumn([
                'check_in_lat',
                'check_in_lng',
                'check_in_distance_meters',
                'check_out_time',
                'check_out_lat',
                'check_out_lng',
                'check_out_distance_meters',
                'status',
                'point_awarded',
            ]);
        });

        Schema::dropIfExists('attendance_sessions');

        Schema::table('hoat_dongs', function (Blueprint $table) {
            $table->dropColumn([
                'location_lat',
                'location_lng',
                'location_radius_meters',
            ]);
        });
    }
};
