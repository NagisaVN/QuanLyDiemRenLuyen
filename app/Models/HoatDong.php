<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HoatDong extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'hoat_dongs';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'thoi_gian_bat_dau' => 'datetime',
            'thoi_gian_ket_thuc' => 'datetime',
            'auto_cong_diem' => 'boolean',
            'is_bat_buoc' => 'boolean',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tieuChi()
    {
        return $this->belongsTo(TieuChi::class);
    }

    public function khoas()
    {
        return $this->belongsToMany(Khoa::class, 'hoat_dong_khoa');
    }

    public function dangKyHoatDongs()
    {
        return $this->hasMany(DangKyHoatDong::class);
    }

    public function diemDanhHoatDongs()
    {
        return $this->hasMany(DiemDanhHoatDong::class);
    }
}
