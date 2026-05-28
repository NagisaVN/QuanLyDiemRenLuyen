<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Khoa extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'khoas';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function lops()
    {
        return $this->hasMany(Lop::class);
    }

    public function hoatDongs()
    {
        return $this->belongsToMany(HoatDong::class, 'hoat_dong_khoa');
    }
}
