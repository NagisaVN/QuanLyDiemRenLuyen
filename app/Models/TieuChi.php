<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TieuChi extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tieu_chis';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function mucTieuChis()
    {
        return $this->hasMany(MucTieuChi::class);
    }

    public function chiTietDanhGias()
    {
        return $this->hasMany(ChiTietDanhGia::class);
    }
}
