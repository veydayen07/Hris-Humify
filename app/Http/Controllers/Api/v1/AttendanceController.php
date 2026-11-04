<?php

namespace App\Http\Controllers\Api\v1;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveAttendanceRequest;
use App\Http\Requests\CheckInRequest;
use App\Http\Requests\CheckOutRequest;
use App\Http\Requests\RejectAttendanceRequest;
use App\Http\Resources\AttendanceResource;
use App\Http\Resources\WorkShiftResource;
use App\Models\Attendance;
use App\Models\Employee;
use App\Services\AttendanceService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AttendanceController extends Controller
{
    public function __construct(
        private AttendanceService $attendanceService,
    ) {}

    public function today(Request $request): JsonResponse
    {
        $data = $this->attendanceService->today($request->user());

        return ApiResponse::success([
            'work_category' => $data['work_category'],
            'available_shifts' => WorkShiftResource::collection($data['available_shifts'])->resolve(),
            'record' => $data['record'] ? (new AttendanceResource($data['record']))->resolve() : null,
            'face_registered' => $data['face_registered'],
            'next_action' => $data['next_action'],
            'attendance_date' => $data['attendance_date'],
        ]);
    }

    public function availableShifts(Request $request): JsonResponse
    {
        $employee = $this->attendanceService->requireActiveEmployee($request->user());
        $shifts = $this->attendanceService->availableShifts($employee);

        return ApiResponse::success(
            WorkShiftResource::collection($shifts)->resolve()
        );
    }

    public function checkIn(CheckInRequest $request): JsonResponse
    {
        $idempotencyKey = $request->header('Idempotency-Key');

        $attendance = $this->attendanceService->checkIn(
            $request->user(),
            (int) $request->input('shift_id'),
            $request->file('photo'),
            (float) $request->input('latitude'),
            (float) $request->input('longitude'),
            $idempotencyKey,
        );

        return ApiResponse::success(
            (new AttendanceResource($attendance))->resolve(),
            status: 201,
        );
    }

    public function checkOut(CheckOutRequest $request): JsonResponse
    {
        $attendance = $this->attendanceService->checkOut(
            $request->user(),
            $request->file('photo'),
            (float) $request->input('latitude'),
            (float) $request->input('longitude'),
        );

        return ApiResponse::success(
            (new AttendanceResource($attendance))->resolve()
        );
    }

    public function records(Request $request): JsonResponse
    {
        $paginator = $this->attendanceService->getRecords($request->user(), $request->all());

        return ApiResponse::success(
            AttendanceResource::collection($paginator)
        );
    }

    public function record(Request $request, int $id): JsonResponse
    {
        $attendance = $this->attendanceService->getRecord($request->user(), $id);

        return ApiResponse::success(
            (new AttendanceResource($attendance))->resolve()
        );
    }

    public function photo(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        if (! $user->isManagerOrAbove()) {
            throw ApiException::forbidden('Only managers and HR can view attendance photos.');
        }

        $attendance = Attendance::query()->findOrFail($id);

        if ($attendance->employee_id === $user->employee_id) {
            throw ApiException::forbidden('Record owner cannot access this photo.');
        }

        if (! $user->isHrOrAbove()) {
            $isDirect = Employee::query()
                ->where('id', $attendance->employee_id)
                ->where('supervisor_id', $user->employee_id)
                ->exists();

            if (! $isDirect) {
                throw ApiException::forbidden('You can only view photos of direct subordinates.');
            }
        }

        $type = $request->query('type', 'check_in');
        $photoPath = $type === 'check_out' ? $attendance->check_out_photo_url : $attendance->check_in_photo_url;

        if (! $photoPath || ! Storage::disk('local')->exists($photoPath)) {
            throw ApiException::validation('Photo not found for this record.', ['photo' => ['Photo file not found.']]);
        }

        $signedUrl = URL::temporarySignedRoute(
            'api.v1.attendance.photo-file',
            now()->addMinutes(config('humify.signed_url_ttl_minutes', 5)),
            ['id' => $attendance->id, 'type' => $type],
        );

        return ApiResponse::success(['url' => $signedUrl]);
    }

    public function downloadPhotoFile(Request $request, int $id): BinaryFileResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'Invalid or expired URL.');
        }

        $attendance = Attendance::query()->findOrFail($id);
        $type = $request->query('type', 'check_in');
        $photoPath = $type === 'check_out' ? $attendance->check_out_photo_url : $attendance->check_in_photo_url;

        if (! $photoPath || ! Storage::disk('local')->exists($photoPath)) {
            abort(404, 'Photo not found.');
        }

        return response()->file(Storage::disk('local')->path($photoPath));
    }

    public function approve(ApproveAttendanceRequest $request, int $id): JsonResponse
    {
        $attendance = Attendance::query()->findOrFail($id);
        $updated = $this->attendanceService->approve($request->user(), $attendance, $request->input('note'));

        return ApiResponse::success(
            (new AttendanceResource($updated))->resolve()
        );
    }

    public function reject(RejectAttendanceRequest $request, int $id): JsonResponse
    {
        $attendance = Attendance::query()->findOrFail($id);
        $updated = $this->attendanceService->reject($request->user(), $attendance, $request->string('reason')->value());

        return ApiResponse::success(
            (new AttendanceResource($updated))->resolve()
        );
    }
}

