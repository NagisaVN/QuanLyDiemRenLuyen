<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'ma_dang_nhap',
        'password',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function sinhVien()
    {
        return $this->hasOne(SinhVien::class);
    }

    public function lopPhuTrachs()
    {
        return $this->hasMany(Lop::class, 'gvcn_id');
    }

    public function dotDanhGiasCreated()
    {
        return $this->hasMany(DotDanhGia::class, 'created_by');
    }

    public function dotDanhGiasUpdated()
    {
        return $this->hasMany(DotDanhGia::class, 'updated_by');
    }

    public function studentNotifications()
    {
        return $this->hasMany(StudentNotification::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }
}
