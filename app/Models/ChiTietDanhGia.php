<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChiTietDanhGia extends Model
{
    use HasFactory;

    protected $table = 'chi_tiet_danh_gias';
    protected $guarded = [];

    public function phieuDanhGia()
    {
        return $this->belongsTo(PhieuDanhGia::class);
    }

    public function tieuChi()
    {
        return $this->belongsTo(TieuChi::class);
    }

    public function mucTieuChi()
    {
        return $this->belongsTo(MucTieuChi::class);
    }
}
