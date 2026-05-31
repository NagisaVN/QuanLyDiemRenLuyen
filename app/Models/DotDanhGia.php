<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DotDanhGia extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_PUBLISHED = 'published';

    protected $table = 'dot_danh_gias';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'ngay_bat_dau_sinh_vien' => 'datetime',
            'ngay_ket_thuc_sinh_vien' => 'datetime',
            'ngay_bat_dau_gvcn' => 'datetime',
            'ngay_ket_thuc_gvcn' => 'datetime',
            'ngay_cong_bo' => 'datetime',
        ];
    }

    public function namHoc()
    {
        return $this->belongsTo(NamHoc::class);
    }

    public function hocKy()
    {
        return $this->belongsTo(HocKy::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function phieuDanhGias()
    {
        return $this->hasMany(PhieuDanhGia::class);
    }

    public function isStudentOpen(): bool
    {
        return $this->trang_thai === self::STATUS_OPEN
            && now()->betweenIncluded($this->ngay_bat_dau_sinh_vien, $this->ngay_ket_thuc_sinh_vien);
    }

    public function isGvcnOpen(): bool
    {
        return $this->trang_thai === self::STATUS_OPEN
            && now()->betweenIncluded($this->ngay_bat_dau_gvcn, $this->ngay_ket_thuc_gvcn);
    }
}
