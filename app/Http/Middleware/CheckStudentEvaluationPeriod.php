<?php

namespace App\Http\Middleware;

use App\Services\DiemRenLuyenService;
use App\Services\DotDanhGiaService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckStudentEvaluationPeriod
{
    public function handle(Request $request, Closure $next): Response
    {
        app(DotDanhGiaService::class)->lockExpiredForms();

        $hocKy = app(DiemRenLuyenService::class)->activeHocKy();

        if (! $hocKy || ! app(DotDanhGiaService::class)->openForStudent($hocKy)) {
            return back()->withErrors(['dot_danh_gia' => 'Đã hết thời hạn nộp phiếu đánh giá.']);
        }

        return $next($request);
    }
}
