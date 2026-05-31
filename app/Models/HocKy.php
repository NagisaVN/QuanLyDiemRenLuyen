<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HocKy extends Model
{
    use HasFactory;

    protected $table = 'hoc_kys';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'ngay_bat_dau' => 'date',
            'ngay_ket_thuc' => 'date',
            'han_tu_danh_gia' => 'datetime',
            'han_gvcn_duyet' => 'datetime',
            'han_hoi_dong_duyet' => 'datetime',
            'ngay_cong_bo' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function namHoc()
    {
        return $this->belongsTo(NamHoc::class);
    }

    public function phieuDanhGias()
    {
        return $this->hasMany(PhieuDanhGia::class);
    }

    public function dotDanhGias()
    {
        return $this->hasMany(DotDanhGia::class);
    }
}
