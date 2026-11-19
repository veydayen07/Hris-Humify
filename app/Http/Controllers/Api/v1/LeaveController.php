<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class LeaveController extends Controller
{
    /**
     * 4.1 Daftar Jenis Cuti / Izin (Master Data)
     */
    public function types(): JsonResponse
    {
        $types = LeaveType::orderBy('id')->get()->map(function ($t) {
            return [
                'id' => (int) $t->id,
                'name' => (string) $t->name,
                'is_paid' => (bool) $t->is_paid,
                'max_days_per_year' => $t->max_days_per_year !== null ? (int) $t->max_days_per_year : null,
                'requires_attachment' => (bool) $t->requires_attachment,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $types,
        ]);
    }

    /**
     * 4.2 Saldo Cuti Karyawan
     */
    public function balances(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;
        if (! $employee) {
            return response()->json([
                'message' => 'Data karyawan tidak ditemukan.',
                'code' => 'NOT_FOUND',
                'errors' => null,
            ], 404);
        }

        $year = (int) $request->input('year', Carbon::now('Asia/Jakarta')->year);

        $balances = LeaveBalance::with('leaveType')
            ->where('employee_id', $employee->id)
            ->where('year', $year)
            ->get();

        $data = $balances->map(function ($b) {
            return [
                'leave_type_id' => (int) $b->leave_type_id,
                'leave_type_name' => (string) ($b->leaveType?->name ?? 'Cuti'),
                'year' => (int) $b->year,
                'total_days' => (float) $b->total_days,
                'used_days' => (float) $b->used_days,
                'remaining_days' => (float) $b->remaining_days,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * 4.3 Riwayat Permohonan Izin / Cuti
     */
    public function requests(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;
        if (! $employee) {
            return response()->json([
                'message' => 'Data karyawan tidak ditemukan.',
                'code' => 'NOT_FOUND',
                'errors' => null,
            ], 404);
        }

        $query = LeaveRequest::with('leaveType')
            ->where('employee_id', $employee->id);

        if ($request->filled('status')) {
            $st = $request->input('status');
            if ($st === 'approved') $st = 'disetujui';
            if ($st === 'rejected') $st = 'ditolak';
            if ($st === 'cancelled') $st = 'dibatalkan';
            $query->where('status', $st);
        }

        if ($request->filled('year')) {
            $query->whereYear('start_date', $request->input('year'));
        }

        $requests = $query->orderBy('created_at', 'desc')->get();

        $data = $requests->map(function ($r) {
            $statusStr = is_object($r->status) ? $r->status->value : (string) $r->status;

            $attachmentUrl = $r->attachment_url;
            if ($attachmentUrl && ! str_starts_with($attachmentUrl, 'http://') && ! str_starts_with($attachmentUrl, 'https://')) {
                $attachmentUrl = url($attachmentUrl);
            }

            return [
                'id' => (int) $r->id,
                'leave_type' => [
                    'id' => (int) $r->leave_type_id,
                    'name' => (string) ($r->leaveType?->name ?? 'Cuti'),
                ],
                'start_date' => $r->start_date ? $r->start_date->format('Y-m-d') : '',
                'end_date' => $r->end_date ? $r->end_date->format('Y-m-d') : '',
                'total_days' => (float) $r->total_days,
                'reason' => (string) ($r->reason ?? ''),
                'attachment_url' => $attachmentUrl,
                'status' => $statusStr,
                'rejection_reason' => $r->rejection_reason,
                'created_at' => $r->created_at ? $r->created_at->format('Y-m-d H:i:s') : '',
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * 4.4 Ajukan Permohonan Cuti / Izin Baru
     */
    public function submit(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;
        if (! $employee) {
            return response()->json([
                'message' => 'Data karyawan tidak ditemukan.',
                'code' => 'NOT_FOUND',
                'errors' => null,
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'leave_type_id' => ['required', 'integer', 'exists:leave_types,id'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'total_days' => ['required', 'numeric', 'min:0.5'],
            'reason' => ['required', 'string', 'max:500'],
            'attachment' => ['nullable', 'file', 'max:5120'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'code' => 'VALIDATION_ERROR',
                'errors' => $validator->errors(),
            ], 422);
        }

        $leaveType = LeaveType::find($request->input('leave_type_id'));
        if (! $leaveType) {
            return response()->json([
                'message' => 'Jenis cuti tidak ditemukan.',
                'code' => 'NOT_FOUND',
                'errors' => null,
            ], 404);
        }

        if ($leaveType->requires_attachment && ! $request->hasFile('attachment')) {
            return response()->json([
                'message' => 'Dokumen lampiran wajib diunggah untuk jenis permohonan ini.',
                'code' => 'VALIDATION_ERROR',
                'errors' => [
                    'attachment' => ['Dokumen lampiran diperlukan.'],
                ],
            ], 422);
        }

        $totalDays = (float) $request->input('total_days');
        $startDate = Carbon::parse($request->input('start_date'));
        $year = (int) $startDate->year;

        // Check Leave Balance
        $balance = LeaveBalance::where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('year', $year)
            ->first();

        if ($balance && ((float) $balance->remaining_days < $totalDays)) {
            return response()->json([
                'message' => 'Sisa saldo cuti Anda tidak mencukupi untuk jumlah hari yang diajukan.',
                'code' => 'INSUFFICIENT_LEAVE_BALANCE',
                'errors' => [
                    'total_days' => ["Sisa saldo cuti: {$balance->remaining_days} hari. Diajukan: {$totalDays} hari."],
                ],
            ], 422);
        }

        // Handle attachment upload
        $attachmentUrl = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $ext = $file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg';
            $filename = 'leave_' . $employee->id . '_' . time() . '.' . $ext;
            $path = $file->storeAs('leave_attachments', $filename, 'public');
            $attachmentUrl = url(Storage::url($path));
        }

        $leaveRequest = LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'total_days' => $totalDays,
            'reason' => $request->input('reason'),
            'attachment_url' => $attachmentUrl,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan cuti berhasil diajukan dan sedang menunggu persetujuan atasan.',
            'data' => [
                'request_id' => (int) $leaveRequest->id,
                'status' => 'pending',
                'total_days' => (float) $leaveRequest->total_days,
            ],
        ], 201);
    }

    /**
     * 4.5 Batalkan Permohonan Cuti (Mobile Karyawan)
     */
    public function cancel(Request $request, string $id): JsonResponse
    {
        $employee = $request->user()->employee;
        if (! $employee) {
            return response()->json([
                'message' => 'Data karyawan tidak ditemukan.',
                'code' => 'NOT_FOUND',
                'errors' => null,
            ], 404);
        }

        $leaveRequest = LeaveRequest::where('id', $id)
            ->where('employee_id', $employee->id)
            ->first();

        if (! $leaveRequest) {
            return response()->json([
                'message' => 'Permohonan cuti tidak ditemukan.',
                'code' => 'NOT_FOUND',
                'errors' => null,
            ], 404);
        }

        $statusStr = is_object($leaveRequest->status) ? $leaveRequest->status->value : (string) $leaveRequest->status;

        if ($statusStr !== 'pending') {
            return response()->json([
                'message' => 'Pengajuan ini tidak dapat dibatalkan karena sudah diproses oleh atasan.',
                'code' => 'REQUEST_CANNOT_BE_CANCELLED',
                'errors' => null,
            ], 422);
        }

        $leaveRequest->update(['status' => 'dibatalkan']);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan berhasil dibatalkan.',
        ]);
    }
}

