<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiemDanhHoatDong extends Model
{
    use HasFactory;

    protected $table = 'diem_danh_hoat_dongs';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['checked_in_at' => 'datetime'];
    }

    public function hoatDong()
    {
        return $this->belongsTo(HoatDong::class);
    }

    public function sinhVien()
    {
        return $this->belongsTo(SinhVien::class);
    }
}
