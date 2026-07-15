<?php

namespace App\Models;

use Carbon\CarbonInterface;
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
            'is_system_sample' => 'boolean',
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
        $now = now();

        return $this->effectiveStatus($now) === self::STATUS_OPEN
            && $now->greaterThanOrEqualTo($this->ngay_bat_dau_sinh_vien)
            && $now->lessThan($this->ngay_ket_thuc_sinh_vien);
    }

    public function isGvcnOpen(): bool
    {
        $now = now();

        return $this->effectiveStatus($now) !== self::STATUS_PUBLISHED
            && $now->greaterThanOrEqualTo($this->ngay_bat_dau_gvcn)
            && $now->lessThan($this->ngay_ket_thuc_gvcn);
    }

    public function effectiveStatus(?CarbonInterface $at = null): string
    {
        $at ??= now();

        if ($this->trang_thai === self::STATUS_PUBLISHED
            || ($this->ngay_cong_bo && $at->greaterThanOrEqualTo($this->ngay_cong_bo))) {
            return self::STATUS_PUBLISHED;
        }

        if ($at->greaterThanOrEqualTo($this->ngay_ket_thuc_sinh_vien)) {
            return self::STATUS_CLOSED;
        }

        if ($at->greaterThanOrEqualTo($this->ngay_bat_dau_sinh_vien)) {
            return self::STATUS_OPEN;
        }

        return self::STATUS_DRAFT;
    }

    public function displayDate(?CarbonInterface $date, string $format = 'd/m/Y H:i'): ?string
    {
        return $date?->copy()->timezone(config('app.display_timezone'))->format($format);
    }
}
