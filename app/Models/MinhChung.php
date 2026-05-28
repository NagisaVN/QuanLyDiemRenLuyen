<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MinhChung extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'minh_chungs';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function sinhVien()
    {
        return $this->belongsTo(SinhVien::class);
    }

    public function phieuDanhGia()
    {
        return $this->belongsTo(PhieuDanhGia::class);
    }

    public function tieuChi()
    {
        return $this->belongsTo(TieuChi::class);
    }
}
