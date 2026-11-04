<?php

namespace App\Http\Controllers\Api\v1;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Http\Resources\AccountResource;
use App\Models\User;
use App\Services\AccountService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function __construct(
        private AccountService $accountService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->ensureSuperAdmin($request);

        $paginator = $this->accountService->list($request->all());

        return ApiResponse::success(
            AccountResource::collection($paginator)
        );
    }

    public function store(CreateAccountRequest $request): JsonResponse
    {
        $this->ensureSuperAdmin($request);

        $user = $this->accountService->create($request->user(), $request->validated());

        return ApiResponse::success(
            (new AccountResource($user))->resolve(),
            status: 201,
        );
    }

    public function update(UpdateAccountRequest $request, string $id): JsonResponse
    {
        $this->ensureSuperAdmin($request);

        $targetUser = User::query()->findOrFail($id);
        $updated = $this->accountService->update($request->user(), $targetUser, $request->validated());

        return ApiResponse::success(
            (new AccountResource($updated))->resolve()
        );
    }

    private function ensureSuperAdmin(Request $request): void
    {
        if (! $request->user()->isSuperAdmin()) {
            throw ApiException::forbidden('Only Super Admin can manage accounts.');
        }
    }
}

