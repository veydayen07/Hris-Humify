<?php

namespace App\Http\Controllers\Api\v1;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSettingsRequest;
use App\Services\SettingsService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct(
        private SettingsService $settingsService,
    ) {}

    public function show(Request $request): JsonResponse
    {
        if (! $request->user()->isHrOrAbove()) {
            throw ApiException::forbidden('Only HR and Super Admin can view settings.');
        }

        $settings = $this->settingsService->get();

        return ApiResponse::success($settings);
    }

    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        if (! $request->user()->isSuperAdmin()) {
            throw ApiException::forbidden('Only Super Admin can update settings.');
        }

        $settings = $this->settingsService->update($request->user(), $request->validated());

        return ApiResponse::success($settings);
    }
}

