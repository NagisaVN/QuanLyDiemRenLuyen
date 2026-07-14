<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const COLUMNS = [
        'ngay_bat_dau_sinh_vien',
        'ngay_ket_thuc_sinh_vien',
        'ngay_bat_dau_gvcn',
        'ngay_ket_thuc_gvcn',
        'ngay_cong_bo',
    ];

    public function up(): void
    {
        $timezone = config('app.display_timezone', 'Asia/Ho_Chi_Minh');

        DB::table('dot_danh_gias')->orderBy('id')->chunkById(100, function ($periods) use ($timezone): void {
            foreach ($periods as $period) {
                $values = [];

                foreach (self::COLUMNS as $column) {
                    $value = $period->{$column};

                    if ($column === 'ngay_cong_bo' && ! $value) {
                        $value = $period->ngay_ket_thuc_gvcn;
                    }

                    $values[$column] = $value
                        ? Carbon::parse($value, $timezone)->utc()->format('Y-m-d H:i:s')
                        : null;
                }

                DB::table('dot_danh_gias')->where('id', $period->id)->update($values);
            }
        });
    }

    public function down(): void
    {
        $timezone = config('app.display_timezone', 'Asia/Ho_Chi_Minh');

        DB::table('dot_danh_gias')->orderBy('id')->chunkById(100, function ($periods) use ($timezone): void {
            foreach ($periods as $period) {
                $values = [];

                foreach (self::COLUMNS as $column) {
                    $value = $period->{$column};
                    $values[$column] = $value
                        ? Carbon::parse($value, 'UTC')->timezone($timezone)->format('Y-m-d H:i:s')
                        : null;
                }

                DB::table('dot_danh_gias')->where('id', $period->id)->update($values);
            }
        });
    }
};
