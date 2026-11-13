<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ManagerController extends Controller
{
    /**
     * 5.1 Antrean Review Presensi Bawahan Langsung
     */
    public function attendanceReviews(Request $request): JsonResponse
    {
        $user = $request->user();
        $isManagerOnly = ! ($user->isHr() || $user->isSuperAdmin());

        $query = Attendance::with(['employee', 'workShift']);

        if ($isManagerOnly) {
            $subordinateIds = Employee::where('supervisor_id', $user->employee_id)->pluck('id')->all();
            $query->whereIn('employee_id', $subordinateIds);
        }

        if ($request->filled('date')) {
            $query->whereDate('attendance_date', $request->input('date'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('review_status')) {
            $query->where('review_status', $request->input('review_status'));
        }

        $records = $query->orderBy('attendance_date', 'desc')->get();

        $data = $records->map(function ($item) {
            return [
                'attendance_id' => (int) $item->id,
                'employee' => [
                    'id' => (string) $item->employee?->id,
                    'employee_code' => (string) ($item->employee?->employee_code ?? ''),
                    'full_name' => (string) ($item->employee?->full_name ?? ''),
                ],
                'attendance_date' => $item->attendance_date ? $item->attendance_date->format('Y-m-d') : '',
                'check_in_time' => $item->check_in_time ? $item->check_in_time->format('H:i:s') : null,
                'check_out_time' => $item->check_out_time ? $item->check_out_time->format('H:i:s') : null,
                'status' => is_object($item->status) ? $item->status->value : (string) $item->status,
                'face_score' => $item->check_in_face_score ? (float) $item->check_in_face_score : 95.0,
                'photo_url' => $item->check_in_photo_url,
                'review_status' => is_object($item->review_status) ? $item->review_status->value : (string) ($item->review_status ?? 'pending'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * 5.2 Keputusan Review Presensi
     */
    public function decideAttendanceReview(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'action' => ['required', 'in:approve,reject'],
            'review_note' => ['required_if:action,reject', 'nullable', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'code' => 'VALIDATION_ERROR',
                'errors' => $validator->errors(),
            ], 422);
        }

        $attendance = Attendance::find($id);
        if (! $attendance) {
            return response()->json([
                'message' => 'Data presensi tidak ditemukan.',
                'code' => 'NOT_FOUND',
                'errors' => null,
            ], 404);
        }

        $action = $request->input('action');
        $reviewStatus = ($action === 'approve') ? 'disetujui' : 'ditolak';

        $attendance->review_status = $reviewStatus;
        $attendance->reviewed_by = $user->id;
        $attendance->reviewed_at = now();
        if ($request->filled('review_note')) {
            $attendance->review_note = $request->input('review_note');
        }
        $attendance->save();

        return response()->json([
            'success' => true,
            'message' => 'Review presensi berhasil disimpan.',
        ]);
    }

    /**
     * 5.3 Antrean Persetujuan Cuti Bawahan Langsung
     */
    public function leaveApprovals(Request $request): JsonResponse
    {
        $user = $request->user();
        $isManagerOnly = ! ($user->isHr() || $user->isSuperAdmin());

        $query = LeaveRequest::with(['employee', 'leaveType']);

        if ($isManagerOnly) {
            $subordinateIds = Employee::where('supervisor_id', $user->employee_id)->pluck('id')->all();
            $query->whereIn('employee_id', $subordinateIds);
        }

        $status = $request->input('status', 'pending');
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $records = $query->orderBy('created_at', 'desc')->get();

        $data = $records->map(function ($item) {
            $year = $item->start_date ? (int) $item->start_date->year : (int) date('Y');
            $balance = LeaveBalance::where('employee_id', $item->employee_id)
                ->where('leave_type_id', $item->leave_type_id)
                ->where('year', $year)
                ->first();

            $remainingBalance = $balance ? (float) $balance->remaining_days : 0.0;
            $statusStr = is_object($item->status) ? $item->status->value : (string) $item->status;

            return [
                'request_id' => (int) $item->id,
                'employee' => [
                    'id' => (string) $item->employee?->id,
                    'full_name' => (string) ($item->employee?->full_name ?? ''),
                    'employee_code' => (string) ($item->employee?->employee_code ?? ''),
                ],
                'leave_type_name' => (string) ($item->leaveType?->name ?? 'Cuti'),
                'start_date' => $item->start_date ? $item->start_date->format('Y-m-d') : '',
                'end_date' => $item->end_date ? $item->end_date->format('Y-m-d') : '',
                'total_days' => (float) $item->total_days,
                'reason' => (string) ($item->reason ?? ''),
                'attachment_url' => $item->attachment_url,
                'status' => $statusStr,
                'remaining_balance' => $remainingBalance,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * 5.4 Keputusan Persetujuan Cuti
     */
    public function decideLeaveApproval(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'action' => ['required', 'in:approve,reject'],
            'rejection_reason' => ['required_if:action,reject', 'nullable', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'code' => 'VALIDATION_ERROR',
                'errors' => $validator->errors(),
            ], 422);
        }

        $leaveRequest = LeaveRequest::find($id);
        if (! $leaveRequest) {
            return response()->json([
                'message' => 'Permohonan cuti tidak ditemukan.',
                'code' => 'NOT_FOUND',
                'errors' => null,
            ], 404);
        }

        $action = $request->input('action');
        $status = ($action === 'approve') ? 'disetujui' : 'ditolak';

        $leaveRequest->status = $status;
        $leaveRequest->approved_by = $user->id;
        $leaveRequest->approved_at = now();
        if ($action === 'reject') {
            $leaveRequest->rejection_reason = $request->input('rejection_reason');
        }
        $leaveRequest->save();

        return response()->json([
            'success' => true,
            'message' => 'Keputusan permohonan berhasil disimpan.',
        ]);
    }
}

