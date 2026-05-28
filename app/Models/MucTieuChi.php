<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MucTieuChi extends Model
{
    use HasFactory;

    protected $table = 'muc_tieu_chis';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function tieuChi()
    {
        return $this->belongsTo(TieuChi::class);
    }
}
