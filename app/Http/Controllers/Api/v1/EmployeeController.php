<?php

namespace App\Http\Controllers\Api\v1;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateEmployeeRequest;
use App\Http\Requests\DeactivateEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Http\Requests\UpdateFaceReferenceRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Services\EmployeeService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EmployeeController extends Controller
{
    public function __construct(
        private EmployeeService $employeeService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->ensureHr($request);

        $paginator = $this->employeeService->list($request->all());

        return ApiResponse::success(
            EmployeeResource::collection($paginator)
        );
    }

    public function store(CreateEmployeeRequest $request): JsonResponse
    {
        $this->ensureHr($request);

        $employee = $this->employeeService->create($request->user(), $request->validated());

        return ApiResponse::success(
            (new EmployeeResource($employee))->resolve(),
            status: 201,
        );
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $this->ensureHr($request);

        $employee = $this->employeeService->show($id);

        return ApiResponse::success(
            (new EmployeeResource($employee))->resolve()
        );
    }

    public function update(UpdateEmployeeRequest $request, string $id): JsonResponse
    {
        $this->ensureHr($request);

        $employee = Employee::query()->findOrFail($id);
        $updated = $this->employeeService->update($request->user(), $employee, $request->validated());

        return ApiResponse::success(
            (new EmployeeResource($updated))->resolve()
        );
    }

    public function deactivate(DeactivateEmployeeRequest $request, string $id): JsonResponse
    {
        $this->ensureHr($request);

        $employee = Employee::query()->findOrFail($id);
        $deactivated = $this->employeeService->deactivate($request->user(), $employee, $request->boolean('confirm'));

        return ApiResponse::success(
            (new EmployeeResource($deactivated))->resolve()
        );
    }

    public function getFaceReference(Request $request, string $id): JsonResponse
    {
        $this->ensureHr($request);

        $employee = Employee::query()->with('faceRegisteredBy')->findOrFail($id);
        $data = $this->employeeService->getFaceReference($employee);

        return ApiResponse::success($data);
    }

    public function updateFaceReference(UpdateFaceReferenceRequest $request, string $id): JsonResponse
    {
        $this->ensureHr($request);

        $employee = Employee::query()->findOrFail($id);
        $updated = $this->employeeService->updateFaceReference(
            $request->user(),
            $employee,
            $request->file('photo'),
            $request->boolean('confirm'),
        );

        return ApiResponse::success(
            (new EmployeeResource($updated))->resolve()
        );
    }

    public function downloadFacePhoto(Request $request, string $id): BinaryFileResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'Invalid or expired URL.');
        }

        $employee = Employee::query()->findOrFail($id);

        if (! $employee->face_reference_photo_url || ! Storage::disk('local')->exists($employee->face_reference_photo_url)) {
            abort(404, 'Photo not found.');
        }

        return response()->file(Storage::disk('local')->path($employee->face_reference_photo_url));
    }

    private function ensureHr(Request $request): void
    {
        if (! $request->user()->isHrOrAbove()) {
            throw ApiException::forbidden('Only HR and Super Admin can access employee administration.');
        }
    }
}

