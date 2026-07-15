<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hoat_dongs', function (Blueprint $table): void {
            $table->dateTime('open_registration_at')->nullable()->after('dia_diem');
            $table->dateTime('close_registration_at')->nullable()->after('open_registration_at');
            $table->index(['trang_thai', 'open_registration_at'], 'activities_status_open_idx');
            $table->index(['trang_thai', 'close_registration_at'], 'activities_status_close_idx');
            $table->index(['trang_thai', 'thoi_gian_ket_thuc'], 'activities_status_end_idx');
        });

        Schema::table('dang_ky_hoat_dongs', function (Blueprint $table): void {
            $table->timestamp('registered_at')->nullable()->after('trang_thai');
            $table->index(['hoat_dong_id', 'trang_thai'], 'activity_registrations_status_idx');
        });

        DB::table('dang_ky_hoat_dongs')->orderBy('id')->chunkById(500, function ($registrations): void {
            foreach ($registrations as $registration) {
                $status = match ($registration->trang_thai) {
                    'pending', 'approved' => 'approved',
                    'attended', 'completed' => 'completed',
                    'rejected', 'cancelled' => 'cancelled',
                    default => 'cancelled',
                };

                DB::table('dang_ky_hoat_dongs')->where('id', $registration->id)->update([
                    'trang_thai' => $status,
                    'registered_at' => $registration->created_at,
                ]);
            }
        });

        $now = now();
        DB::table('hoat_dongs')->orderBy('id')->chunkById(200, function ($activities) use ($now): void {
            foreach ($activities as $activity) {
                if ($activity->trang_thai === 'cancelled') {
                    continue;
                }

                if (! $activity->thoi_gian_bat_dau || ! $activity->thoi_gian_ket_thuc) {
                    DB::table('hoat_dongs')->where('id', $activity->id)->update(['trang_thai' => 'draft']);
                    continue;
                }

                $start = Carbon::parse($activity->thoi_gian_bat_dau);
                $end = Carbon::parse($activity->thoi_gian_ket_thuc);
                $open = Carbon::parse($activity->created_at ?? $start->copy()->subDay());
                if ($open->greaterThanOrEqualTo($start)) {
                    $open = $start->copy()->subMinute();
                }

                $status = match (true) {
                    $now->greaterThanOrEqualTo($end) => 'completed',
                    $now->greaterThanOrEqualTo($start) => 'registration_closed',
                    $now->greaterThanOrEqualTo($open) => 'open',
                    default => 'scheduled',
                };

                DB::table('hoat_dongs')->where('id', $activity->id)->update([
                    'open_registration_at' => $open,
                    'close_registration_at' => $start,
                    'trang_thai' => $status,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('dang_ky_hoat_dongs', function (Blueprint $table): void {
            $table->dropIndex('activity_registrations_status_idx');
            $table->dropColumn('registered_at');
        });

        Schema::table('hoat_dongs', function (Blueprint $table): void {
            $table->dropIndex('activities_status_open_idx');
            $table->dropIndex('activities_status_close_idx');
            $table->dropIndex('activities_status_end_idx');
            $table->dropColumn(['open_registration_at', 'close_registration_at']);
        });
    }
};
