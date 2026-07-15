<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentNotification extends Model
{
    use HasFactory;

    public const TYPE_EVALUATION_OPEN = 'EVALUATION_OPEN';
    public const TYPE_EVALUATION_REMINDER = 'EVALUATION_REMINDER';
    public const TYPE_EVALUATION_CLOSED = 'EVALUATION_CLOSED';
    public const TYPE_SYSTEM_ACTIVITY = 'SYSTEM_ACTIVITY';

    protected $table = 'notifications';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_read' => 'boolean', 'expires_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isEvaluation(): bool
    {
        return in_array($this->type, [self::TYPE_EVALUATION_OPEN, self::TYPE_EVALUATION_REMINDER, self::TYPE_EVALUATION_CLOSED], true);
    }
}
