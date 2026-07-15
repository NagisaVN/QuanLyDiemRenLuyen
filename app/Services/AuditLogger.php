<?php

namespace App\Services;

use App\Models\SystemLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AuditLogger
{
    private const SENSITIVE_KEYS = [
        'password', 'password_confirmation', 'current_password', 'token', 'remember_token',
    ];

    public function write(
        string $action,
        Model|string|null $subject = null,
        ?array $metadata = null,
        ?Request $request = null,
        ?int $actorId = null,
        ?string $description = null,
    ): void {
        try {
            if (! Schema::hasTable('logs')) {
                return;
            }

            $request ??= request();
            $subjectType = $subject instanceof Model ? $subject->getMorphClass() : $subject;
            $subjectId = $subject instanceof Model ? $subject->getKey() : null;

            SystemLog::query()->create([
                'user_id' => $actorId ?? $request->user()?->getAuthIdentifier(),
                'hanh_dong' => $action,
                'doi_tuong' => $subjectType,
                'doi_tuong_id' => $subjectId,
                'noi_dung' => $description,
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000),
                'metadata' => $this->sanitize($metadata ?? []),
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function sanitize(array $metadata): array
    {
        foreach (self::SENSITIVE_KEYS as $key) {
            Arr::forget($metadata, $key);
        }

        return $metadata;
    }
}
