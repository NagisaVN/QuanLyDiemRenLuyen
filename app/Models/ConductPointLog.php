<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConductPointLog extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function sinhVien()
    {
        return $this->belongsTo(SinhVien::class);
    }

    public function hoatDong()
    {
        return $this->belongsTo(HoatDong::class);
    }
}
