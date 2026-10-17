<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\UserStatus;
use App\Support\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Domain-level failure that already knows which HTTP status it maps to.
 * Services throw these so controllers stay free of error-shaping code.
 */
class ApiException extends Exception
{
    /**
     * @param  array<string, array<int, string>>  $errors
     */
    public function __construct(
        string $message,
        private readonly int $status = Response::HTTP_BAD_REQUEST,
        private readonly array $errors = [],
    ) {
        parent::__construct($message);
    }

    public static function invalidCredentials(): self
    {
        return new self('The provided credentials are incorrect.', Response::HTTP_UNAUTHORIZED);
    }

    public static function accountUnavailable(UserStatus $status): self
    {
        return new self($status->denialMessage(), Response::HTTP_FORBIDDEN);
    }

    public static function emailNotVerified(): self
    {
        return new self('Your email address is not verified.', Response::HTTP_FORBIDDEN);
    }

    public static function invalidVerificationLink(): self
    {
        return new self('The verification link is invalid or has expired.', Response::HTTP_FORBIDDEN);
    }

    public function render(Request $request): JsonResponse
    {
        return ApiResponse::error($this->getMessage(), $this->status, $this->errors);
    }
}
