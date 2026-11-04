<?php

namespace App\Http\Controllers\Api\v1;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateLeaveTypeRequest;
use App\Http\Requests\RejectLeaveRequest;
use App\Http\Requests\SubmitLeaveRequest;
use App\Http\Requests\UpdateLeaveTypeRequest;
use App\Http\Requests\UpsertLeaveBalanceRequest;
use App\Http\Resources\LeaveBalanceResource;
use App\Http\Resources\LeaveRequestResource;
use App\Http\Resources\LeaveTypeResource;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\LeaveService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LeaveController extends Controller
{
    public function __construct(
        private LeaveService $leaveService,
    ) {}

    public function types(Request $request): JsonResponse
    {
        $types = $request->user()->isHrOrAbove() && $request->boolean('all')
            ? $this->leaveService->getAllTypes()
            : $this->leaveService->getActiveTypes();

        return ApiResponse::success(
            LeaveTypeResource::collection($types)->resolve()
        );
    }

    public function createType(CreateLeaveTypeRequest $request): JsonResponse
    {
        if (! $request->user()->isHrOrAbove()) {
            throw ApiException::forbidden('Only HR and Super Admin can create leave types.');
        }

        $type = $this->leaveService->createType($request->user(), $request->validated());

        return ApiResponse::success(
            (new LeaveTypeResource($type))->resolve(),
            status: 201,
        );
    }

    public function updateType(UpdateLeaveTypeRequest $request, int $id): JsonResponse
    {
        if (! $request->user()->isHrOrAbove()) {
            throw ApiException::forbidden('Only HR and Super Admin can update leave types.');
        }

        $type = LeaveType::query()->findOrFail($id);
        $updated = $this->leaveService->updateType($request->user(), $type, $request->validated());

        return ApiResponse::success(
            (new LeaveTypeResource($updated))->resolve()
        );
    }

    public function balances(Request $request): JsonResponse
    {
        $year = $request->has('year') ? (int) $request->input('year') : null;
        $employeeId = $request->input('employee_id');

        $balances = $this->leaveService->getBalances($request->user(), $employeeId, $year);

        return ApiResponse::success(
            LeaveBalanceResource::collection($balances)->resolve()
        );
    }

    public function upsertBalance(UpsertLeaveBalanceRequest $request): JsonResponse
    {
        if (! $request->user()->isHrOrAbove()) {
            throw ApiException::forbidden('Only HR and Super Admin can upsert leave balances.');
        }

        $balance = $this->leaveService->upsertBalance(
            $request->user(),
            $request->string('employee_id')->value(),
            (int) $request->input('leave_type_id'),
            (int) $request->input('year'),
            (float) $request->input('total_days'),
        );

        return ApiResponse::success(
            (new LeaveBalanceResource($balance))->resolve()
        );
    }

    public function requests(Request $request): JsonResponse
    {
        $paginator = $this->leaveService->getRequests($request->user(), $request->all());

        return ApiResponse::success(
            LeaveRequestResource::collection($paginator)
        );
    }

    public function submit(SubmitLeaveRequest $request): JsonResponse
    {
        $leaveRequest = $this->leaveService->submit(
            $request->user(),
            (int) $request->input('leave_type_id'),
            $request->string('start_date')->value(),
            $request->string('end_date')->value(),
            $request->input('reason'),
            $request->file('attachment'),
        );

        return ApiResponse::success(
            (new LeaveRequestResource($leaveRequest))->resolve(),
            status: 201,
        );
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $leaveRequest = LeaveRequest::query()
            ->with(['leaveType', 'employee.department', 'employee.position', 'approver.employee'])
            ->findOrFail($id);

        $this->assertCanViewRequest($request->user(), $leaveRequest);

        return ApiResponse::success(
            (new LeaveRequestResource($leaveRequest))->resolve()
        );
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $leaveRequest = LeaveRequest::query()->findOrFail($id);
        $canceled = $this->leaveService->cancel($request->user(), $leaveRequest);

        return ApiResponse::success(
            (new LeaveRequestResource($canceled))->resolve()
        );
    }

    public function attachment(Request $request, int $id): JsonResponse
    {
        $leaveRequest = LeaveRequest::query()->findOrFail($id);
        $this->assertCanViewRequest($request->user(), $leaveRequest);

        if (! $leaveRequest->attachment_url || ! Storage::disk('local')->exists($leaveRequest->attachment_url)) {
            throw ApiException::validation('Attachment not found.', ['attachment' => ['Attachment file not found.']]);
        }

        $signedUrl = URL::temporarySignedRoute(
            'api.v1.leave.attachment-file',
            now()->addMinutes(config('humify.signed_url_ttl_minutes', 5)),
            ['id' => $leaveRequest->id],
        );

        return ApiResponse::success(['url' => $signedUrl]);
    }

    public function downloadAttachmentFile(Request $request, int $id): BinaryFileResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'Invalid or expired URL.');
        }

        $leaveRequest = LeaveRequest::query()->findOrFail($id);

        if (! $leaveRequest->attachment_url || ! Storage::disk('local')->exists($leaveRequest->attachment_url)) {
            abort(404, 'Attachment not found.');
        }

        return response()->file(Storage::disk('local')->path($leaveRequest->attachment_url));
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $leaveRequest = LeaveRequest::query()->with('leaveType')->findOrFail($id);

        $result = $this->leaveService->approve(
            $request->user(),
            $leaveRequest,
            $request->input('note'),
        );

        return ApiResponse::success(
            (new LeaveRequestResource($result['request']))->resolve(),
            meta: ['skipped_dates' => $result['skipped_dates']],
        );
    }

    public function reject(RejectLeaveRequest $request, int $id): JsonResponse
    {
        $leaveRequest = LeaveRequest::query()->with('leaveType')->findOrFail($id);

        $updated = $this->leaveService->reject(
            $request->user(),
            $leaveRequest,
            $request->string('reason')->value(),
        );

        return ApiResponse::success(
            (new LeaveRequestResource($updated))->resolve()
        );
    }

    private function assertCanViewRequest(mixed $user, LeaveRequest $request): void
    {
        if ($user->isHrOrAbove()) {
            return;
        }

        if ($user->employee_id === $request->employee_id) {
            return;
        }

        if ($user->isManagerOrAbove()) {
            $isDirect = Employee::query()
                ->where('id', $request->employee_id)
                ->where('supervisor_id', $user->employee_id)
                ->exists();

            if ($isDirect) {
                return;
            }
        }

        throw ApiException::forbidden('You do not have permission to view this leave request.');
    }
}

