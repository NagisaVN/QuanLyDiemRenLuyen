<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceSession;
use App\Models\HoatDong;
use App\Services\HoatDongService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AttendanceController extends Controller
{
    public function storeSession(Request $request, HoatDongService $service): JsonResponse
    {
        $data = $request->validate([
            'activityId' => ['required', 'integer', 'exists:hoat_dongs,id'],
            'type' => ['required', 'in:check_in,check_out'],
            'startAt' => ['required', 'date'],
            'endAt' => ['required', 'date', 'after:startAt'],
        ]);

        $activity = HoatDong::findOrFail($data['activityId']);
        $session = $service->createAttendanceSession(
            $activity,
            $request->user(),
            $data['type'],
            Carbon::parse($data['startAt']),
            Carbon::parse($data['endAt'])
        );

        return response()->json([
            'message' => 'Đã mở phiên điểm danh.',
            'session' => [
                'id' => $session->id,
                'type' => $session->type,
                'start_at' => $session->start_at?->toDateTimeString(),
                'end_at' => $session->end_at?->toDateTimeString(),
            ],
            'checkinUrl' => route('sinh-vien.attendance.scan', [
                'sessionId' => $session->id,
                'token' => $session->token,
            ]),
        ]);
    }

    public function scan(Request $request, HoatDongService $service): JsonResponse
    {
        $data = $request->validate([
            'sessionId' => ['required', 'integer', 'exists:attendance_sessions,id'],
            'token' => ['required', 'string'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
        ]);

        $attendance = $service->scanAttendance(
            AttendanceSession::with('hoatDong')->findOrFail($data['sessionId']),
            $request->user()->sinhVien,
            $request,
            [
                'latitude' => (float) $data['latitude'],
                'longitude' => (float) $data['longitude'],
                'accuracy' => isset($data['accuracy']) ? (float) $data['accuracy'] : null,
            ]
        );

        $message = $attendance->check_out_time
            ? 'Điểm danh cuối giờ thành công.'
            : 'Điểm danh đầu giờ thành công.';

        return response()->json([
            'message' => $message,
            'status' => $attendance->status,
            'attendance' => [
                'check_in_time' => $attendance->checked_in_at?->toDateTimeString(),
                'check_out_time' => $attendance->check_out_time?->toDateTimeString(),
                'point_awarded' => $attendance->point_awarded,
            ],
        ]);
    }

    public function approve(Request $request, HoatDong $hoatDong, HoatDongService $service): JsonResponse
    {
        $count = $service->approveAttendance($hoatDong, $request->user());

        return response()->json([
            'message' => "Đã duyệt cộng điểm cho {$count} sinh viên.",
            'approved_count' => $count,
        ]);
    }
}
