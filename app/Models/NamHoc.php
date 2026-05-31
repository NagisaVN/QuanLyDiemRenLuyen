<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NamHoc extends Model
{
    use HasFactory;

    protected $table = 'nam_hocs';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'ngay_bat_dau' => 'date',
            'ngay_ket_thuc' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function hocKys()
    {
        return $this->hasMany(HocKy::class);
    }

    public function dotDanhGias()
    {
        return $this->hasMany(DotDanhGia::class);
    }
}
