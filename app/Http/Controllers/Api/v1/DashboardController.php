<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttendanceResource;
use App\Services\DashboardService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboardService,
    ) {}

    public function employee(Request $request): JsonResponse
    {
        $data = $this->dashboardService->employee($request->user());

        return ApiResponse::success([
            'leave_balances' => $data['leave_balances'],
            'pending_requests_count' => $data['pending_requests_count'],
            'recent_attendances' => AttendanceResource::collection($data['recent_attendances'])->resolve(),
        ]);
    }

    public function team(Request $request): JsonResponse
    {
        $data = $this->dashboardService->team($request->user());

        return ApiResponse::success($data);
    }

    public function hr(Request $request): JsonResponse
    {
        $data = $this->dashboardService->hr($request->user());

        return ApiResponse::success($data);
    }
}

