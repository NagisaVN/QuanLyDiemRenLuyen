<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lop extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'lops';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function khoa()
    {
        return $this->belongsTo(Khoa::class);
    }

    public function gvcn()
    {
        return $this->belongsTo(User::class, 'gvcn_id');
    }

    public function sinhViens()
    {
        return $this->hasMany(SinhVien::class);
    }
}
