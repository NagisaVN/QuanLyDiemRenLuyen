<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ThongBao extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'thong_baos';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'het_han_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }
}
