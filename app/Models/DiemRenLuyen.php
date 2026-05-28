<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiemRenLuyen extends Model
{
    use HasFactory;

    protected $table = 'diem_ren_luyens';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['cong_bo_at' => 'datetime'];
    }

    public function sinhVien()
    {
        return $this->belongsTo(SinhVien::class);
    }

    public function hocKy()
    {
        return $this->belongsTo(HocKy::class);
    }

    public function phieuDanhGia()
    {
        return $this->belongsTo(PhieuDanhGia::class);
    }
}
