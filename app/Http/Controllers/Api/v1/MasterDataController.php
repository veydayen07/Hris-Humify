<?php

namespace App\Http\Controllers\Api\v1;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateDepartmentRequest;
use App\Http\Requests\CreatePositionRequest;
use App\Http\Requests\CreateShiftRequest;
use App\Http\Requests\CreateWorkLocationRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Http\Requests\UpdatePositionRequest;
use App\Http\Requests\UpdateShiftRequest;
use App\Http\Requests\UpdateWorkLocationRequest;
use App\Http\Resources\DepartmentResource;
use App\Http\Resources\PositionResource;
use App\Http\Resources\WorkLocationResource;
use App\Http\Resources\WorkShiftResource;
use App\Models\Department;
use App\Models\Position;
use App\Models\WorkLocation;
use App\Models\WorkShift;
use App\Services\MasterDataService;
use App\Services\ReportService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MasterDataController extends Controller
{
    public function __construct(
        private MasterDataService $masterDataService,
        private ReportService $reportService,
    ) {}

    // Departments
    public function departments(): JsonResponse
    {
        $departments = $this->masterDataService->getDepartments();

        return ApiResponse::success(
            DepartmentResource::collection($departments)->resolve()
        );
    }

    public function createDepartment(CreateDepartmentRequest $request): JsonResponse
    {
        $this->ensureHr($request);

        $department = $this->masterDataService->createDepartment($request->user(), $request->validated());

        return ApiResponse::success(
            (new DepartmentResource($department))->resolve(),
            status: 201,
        );
    }

    public function updateDepartment(UpdateDepartmentRequest $request, int $id): JsonResponse
    {
        $this->ensureHr($request);

        $department = Department::query()->findOrFail($id);
        $updated = $this->masterDataService->updateDepartment($request->user(), $department, $request->validated());

        return ApiResponse::success(
            (new DepartmentResource($updated))->resolve()
        );
    }

    // Positions
    public function positions(Request $request): JsonResponse
    {
        $deptId = $request->has('department_id') ? (int) $request->input('department_id') : null;
        $positions = $this->masterDataService->getPositions($deptId);

        return ApiResponse::success(
            PositionResource::collection($positions)->resolve()
        );
    }

    public function createPosition(CreatePositionRequest $request): JsonResponse
    {
        $this->ensureHr($request);

        $position = $this->masterDataService->createPosition($request->user(), $request->validated());

        return ApiResponse::success(
            (new PositionResource($position))->resolve(),
            status: 201,
        );
    }

    public function updatePosition(UpdatePositionRequest $request, int $id): JsonResponse
    {
        $this->ensureHr($request);

        $position = Position::query()->findOrFail($id);
        $updated = $this->masterDataService->updatePosition($request->user(), $position, $request->validated());

        return ApiResponse::success(
            (new PositionResource($updated))->resolve()
        );
    }

    // Shifts
    public function shifts(Request $request): JsonResponse
    {
        $this->ensureHr($request);

        $shifts = $this->masterDataService->getAllShifts();

        return ApiResponse::success(
            WorkShiftResource::collection($shifts)->resolve()
        );
    }

    public function createShift(CreateShiftRequest $request): JsonResponse
    {
        $this->ensureHr($request);

        $shift = $this->masterDataService->createShift($request->user(), $request->validated());

        return ApiResponse::success(
            (new WorkShiftResource($shift))->resolve(),
            status: 201,
        );
    }

    public function updateShift(UpdateShiftRequest $request, int $id): JsonResponse
    {
        $this->ensureHr($request);

        $shift = WorkShift::query()->findOrFail($id);
        $updated = $this->masterDataService->updateShift($request->user(), $shift, $request->validated());

        return ApiResponse::success(
            (new WorkShiftResource($updated))->resolve()
        );
    }

    // Work Locations
    public function workLocations(Request $request): JsonResponse
    {
        $this->ensureHr($request);

        $locations = $this->masterDataService->getWorkLocations();

        return ApiResponse::success(
            WorkLocationResource::collection($locations)->resolve()
        );
    }

    public function createWorkLocation(CreateWorkLocationRequest $request): JsonResponse
    {
        $this->ensureHr($request);

        $location = $this->masterDataService->createWorkLocation($request->user(), $request->validated());

        return ApiResponse::success(
            (new WorkLocationResource($location))->resolve(),
            status: 201,
        );
    }

    public function updateWorkLocation(UpdateWorkLocationRequest $request, int $id): JsonResponse
    {
        $this->ensureHr($request);

        $location = WorkLocation::query()->findOrFail($id);
        $updated = $this->masterDataService->updateWorkLocation($request->user(), $location, $request->validated());

        return ApiResponse::success(
            (new WorkLocationResource($updated))->resolve()
        );
    }

    // Reports
    public function monthlyReport(Request $request): Response
    {
        $this->ensureHr($request);

        $data = $this->reportService->monthlyAttendanceReport($request->all());

        if ($request->query('format') === 'csv') {
            $csv = $this->reportService->exportCsv($data);

            return response($csv, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="attendance_report_'.$data['month'].'.csv"',
            ]);
        }

        return ApiResponse::success($data);
    }

    private function ensureHr(Request $request): void
    {
        if (! $request->user()->isHrOrAbove()) {
            throw ApiException::forbidden('Only HR and Super Admin can manage master data.');
        }
    }
}

