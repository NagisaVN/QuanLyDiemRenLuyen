<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HoatDong extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_OPEN = 'open';
    public const STATUS_REGISTRATION_CLOSED = 'registration_closed';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'hoat_dongs';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'open_registration_at' => 'datetime',
            'close_registration_at' => 'datetime',
            'thoi_gian_bat_dau' => 'datetime',
            'thoi_gian_ket_thuc' => 'datetime',
            'location_lat' => 'float',
            'location_lng' => 'float',
            'auto_cong_diem' => 'boolean',
            'is_bat_buoc' => 'boolean',
        ];
    }

    public function effectiveStatus($at = null): string
    {
        $at ??= now();

        if (in_array($this->trang_thai, [self::STATUS_DRAFT, self::STATUS_CANCELLED], true)) {
            return $this->trang_thai;
        }

        if (! $this->open_registration_at || ! $this->close_registration_at || ! $this->thoi_gian_bat_dau || ! $this->thoi_gian_ket_thuc) {
            return self::STATUS_DRAFT;
        }

        return match (true) {
            $at->greaterThanOrEqualTo($this->thoi_gian_ket_thuc) => self::STATUS_COMPLETED,
            $at->greaterThanOrEqualTo($this->close_registration_at) => self::STATUS_REGISTRATION_CLOSED,
            $at->greaterThanOrEqualTo($this->open_registration_at) => self::STATUS_OPEN,
            default => self::STATUS_SCHEDULED,
        };
    }

    public function canRegister($at = null): bool
    {
        return $this->effectiveStatus($at) === self::STATUS_OPEN;
    }

    public function displayDate($date, string $format = 'd/m/Y H:i'): ?string
    {
        return $date?->copy()->timezone(config('app.display_timezone'))->format($format);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tieuChi()
    {
        return $this->belongsTo(TieuChi::class);
    }

    public function khoas()
    {
        return $this->belongsToMany(Khoa::class, 'hoat_dong_khoa');
    }

    public function dangKyHoatDongs()
    {
        return $this->hasMany(DangKyHoatDong::class);
    }

    public function diemDanhHoatDongs()
    {
        return $this->hasMany(DiemDanhHoatDong::class);
    }

    public function attendanceSessions()
    {
        return $this->hasMany(AttendanceSession::class);
    }

    public function conductPointLogs()
    {
        return $this->hasMany(ConductPointLog::class);
    }
}
