<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiemDanhHoatDong extends Model
{
    use HasFactory;

    protected $table = 'diem_danh_hoat_dongs';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',
            'check_out_time' => 'datetime',
            'check_in_lat' => 'float',
            'check_in_lng' => 'float',
            'check_out_lat' => 'float',
            'check_out_lng' => 'float',
            'point_awarded' => 'boolean',
        ];
    }

    public function hoatDong()
    {
        return $this->belongsTo(HoatDong::class);
    }

    public function sinhVien()
    {
        return $this->belongsTo(SinhVien::class);
    }

    public function checkInSession()
    {
        return $this->belongsTo(AttendanceSession::class, 'check_in_session_id');
    }

    public function checkOutSession()
    {
        return $this->belongsTo(AttendanceSession::class, 'check_out_session_id');
    }
}
