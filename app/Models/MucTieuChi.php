<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MucTieuChi extends Model
{
    use HasFactory;

    public const TYPE_HEADING = 'heading';
    public const TYPE_ITEM = 'item';

    protected $table = 'muc_tieu_chis';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'diem_toi_da' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function tieuChi()
    {
        return $this->belongsTo(TieuChi::class);
    }

    public function chiTietDanhGias()
    {
        return $this->hasMany(ChiTietDanhGia::class);
    }

    public function minhChungs()
    {
        return $this->hasMany(MinhChung::class);
    }
}
