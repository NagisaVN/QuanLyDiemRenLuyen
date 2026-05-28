<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DangKyHoatDong extends Model
{
    use HasFactory;

    protected $table = 'dang_ky_hoat_dongs';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['approved_at' => 'datetime'];
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
