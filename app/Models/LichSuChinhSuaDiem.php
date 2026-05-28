<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LichSuChinhSuaDiem extends Model
{
    use HasFactory;

    protected $table = 'lich_su_chinh_sua_diems';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function phieuDanhGia()
    {
        return $this->belongsTo(PhieuDanhGia::class);
    }
}
