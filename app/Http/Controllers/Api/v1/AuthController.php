<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\UpdateContactRequest;
use App\Http\Resources\EmployeeResource;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Services\ProfileService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private ProfileService $profileService,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->string('username')->value(),
            $request->string('password')->value(),
            $request->string('device_name')->value(),
            $request,
        );

        return ApiResponse::success([
            'token' => $result['token'],
            'user' => (new UserResource($result['user']))->resolve(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user(), $request);

        return ApiResponse::success(['message' => 'Successfully logged out.']);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['employee.department', 'employee.position', 'employee.supervisor']);

        return ApiResponse::success(
            (new EmployeeResource($user->employee))->resolve()
        );
    }

    public function updateContact(UpdateContactRequest $request): JsonResponse
    {
        $employee = $this->profileService->updateContact(
            $request->user(),
            $request->validated(),
        );

        return ApiResponse::success(
            (new EmployeeResource($employee))->resolve()
        );
    }

    public function registerFaceReference(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        $employee = $this->profileService->registerFaceReference(
            $request->user(),
            $request->file('photo'),
        );

        return ApiResponse::success(
            (new EmployeeResource($employee))->resolve(),
            status: 201,
        );
    }
}

