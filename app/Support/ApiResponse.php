<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use JsonSerializable;

/**
 * Single place where the API envelope is defined, so every controller,
 * middleware and exception renderer emits the exact same shape.
 */
final class ApiResponse
{
    /**
     * @param  array<string, mixed>|Arrayable<string, mixed>|JsonSerializable|null  $data
     */
    public static function success(
        string $message,
        array|Arrayable|JsonSerializable|null $data = null,
        int $status = Response::HTTP_OK,
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data ?? new \stdClass,
        ], $status);
    }

    /**
     * @param  array<string, mixed>|Arrayable<string, mixed>|JsonSerializable|null  $data
     */
    public static function created(
        string $message,
        array|Arrayable|JsonSerializable|null $data = null,
    ): JsonResponse {
        return self::success($message, $data, Response::HTTP_CREATED);
    }

    /**
     * @param  array<string, array<int, string>>  $errors
     */
    public static function error(
        string $message,
        int $status = Response::HTTP_BAD_REQUEST,
        array $errors = [],
    ): JsonResponse {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== []) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }
}
