<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhieuDanhGia extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_REVIEWED = 'reviewed';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_LOCKED = 'locked';

    public const STUDENT_EDITABLE_STATUSES = [
        self::STATUS_DRAFT,
    ];

    public const GVCN_REVIEWABLE_STATUSES = [
        self::STATUS_SUBMITTED,
    ];

    public const FINAL_REVIEWABLE_STATUSES = [
        self::STATUS_REVIEWED,
    ];

    protected $table = 'phieu_danh_gias';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
            'locked_at' => 'datetime',
        ];
    }

    public function sinhVien()
    {
        return $this->belongsTo(SinhVien::class);
    }

    public function hocKy()
    {
        return $this->belongsTo(HocKy::class);
    }

    public function dotDanhGia()
    {
        return $this->belongsTo(DotDanhGia::class);
    }

    public function chiTietDanhGias()
    {
        return $this->hasMany(ChiTietDanhGia::class);
    }

    public function minhChungs()
    {
        return $this->hasMany(MinhChung::class);
    }

    public function lichSuChinhSuaDiems()
    {
        return $this->hasMany(LichSuChinhSuaDiem::class);
    }

    public function diemRenLuyen()
    {
        return $this->hasOne(DiemRenLuyen::class);
    }

    public function canStudentEditStatus(): bool
    {
        return in_array($this->trang_thai, self::STUDENT_EDITABLE_STATUSES, true);
    }

    public function canGvcnReviewStatus(): bool
    {
        return in_array($this->trang_thai, self::GVCN_REVIEWABLE_STATUSES, true);
    }

    public function canFinalReviewStatus(): bool
    {
        return in_array($this->trang_thai, self::FINAL_REVIEWABLE_STATUSES, true);
    }
}
