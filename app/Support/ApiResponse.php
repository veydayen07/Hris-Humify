<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator;

class ApiResponse
{
    /**
     * @param  mixed  $data
     * @param  array<string, mixed>  $meta
     */
    public static function success(mixed $data = null, array $meta = [], int $status = 200): JsonResponse
    {
        if ($data instanceof ResourceCollection && $data->resource instanceof AbstractPaginator) {
            $paginator = $data->resource;

            return response()->json([
                'data' => $data->resolve(),
                'meta' => array_merge([
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                ], $meta),
            ], $status);
        }

        if ($data instanceof JsonResource) {
            $data = $data->resolve();
        }

        return response()->json([
            'data' => $data,
            'meta' => (object) $meta,
        ], $status);
    }

    /**
     * @param  array<string, mixed>  $errors
     */
    public static function error(
        string $message,
        string $code,
        int $status = 400,
        array $errors = [],
        mixed $data = null,
        array $meta = [],
    ): JsonResponse {
        $payload = [
            'message' => $message,
            'code' => $code,
            'errors' => (object) $errors,
        ];

        if ($data !== null) {
            $payload['data'] = $data;
        }

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }
}
