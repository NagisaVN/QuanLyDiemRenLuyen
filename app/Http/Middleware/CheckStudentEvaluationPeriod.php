<?php

namespace App\Http\Middleware;

use App\Services\DotDanhGiaService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckStudentEvaluationPeriod
{
    public function handle(Request $request, Closure $next): Response
    {
        $service = app(DotDanhGiaService::class);
        if (! $request->isMethodSafe() && ! $service->getCurrentStudentPeriod()) {
            return back()->withErrors(['dot_danh_gia' => 'Hiện chưa có đợt đánh giá đang mở.']);
        }

        return $next($request);
    }
}
