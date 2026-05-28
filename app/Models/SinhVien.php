<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SinhVien extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'sinh_viens';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['ngay_sinh' => 'date'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lop()
    {
        return $this->belongsTo(Lop::class);
    }

    public function phieuDanhGias()
    {
        return $this->hasMany(PhieuDanhGia::class);
    }

    public function diemRenLuyens()
    {
        return $this->hasMany(DiemRenLuyen::class);
    }

    public function hoatDongs()
    {
        return $this->belongsToMany(HoatDong::class, 'dang_ky_hoat_dongs')
            ->withPivot(['trang_thai', 'approved_by', 'approved_at', 'ghi_chu'])
            ->withTimestamps();
    }
}
