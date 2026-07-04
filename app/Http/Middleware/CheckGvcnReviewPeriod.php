<?php

namespace App\Http\Middleware;

use App\Models\PhieuDanhGia;
use App\Services\DotDanhGiaService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckGvcnReviewPeriod
{
    public function handle(Request $request, Closure $next): Response
    {
        $service = app(DotDanhGiaService::class);
        $service->lockExpiredForms($request->user());

        $phieu = $request->route('phieu');
        $minhChung = $request->route('minhChung');
        $dot = $phieu instanceof PhieuDanhGia
            ? $phieu->loadMissing('dotDanhGia')->dotDanhGia
            : $minhChung?->loadMissing('phieuDanhGia.dotDanhGia')->phieuDanhGia?->dotDanhGia;
        $dot ??= $service->getCurrentTeacherPeriod();

        if (! $service->openForGvcn($dot)) {
            return back()->withErrors(['dot_danh_gia' => 'Đã hết thời hạn duyệt phiếu đánh giá.']);
        }

        return $next($request);
    }
}
