<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Services\Auth\PasswordService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Password;

class PasswordController extends Controller
{
    /**
     * Returned regardless of whether the address is registered, so the endpoint
     * cannot be used to enumerate accounts.
     */
    private const GENERIC_RESET_LINK_MESSAGE = 'If an account matches that email address, a password reset link has been sent.';

    public function __construct(private readonly PasswordService $passwords) {}

    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        $this->passwords->sendResetLink($request->string('email')->toString());

        return ApiResponse::success(self::GENERIC_RESET_LINK_MESSAGE);
    }

    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $status = $this->passwords->reset($request->validated());

        if ($status !== Password::PASSWORD_RESET) {
            return ApiResponse::error(
                'Password reset failed.',
                Response::HTTP_UNPROCESSABLE_ENTITY,
                ['email' => [__($status)]],
            );
        }

        return ApiResponse::success('Password has been reset successfully. Please log in again.');
    }

    public function change(ChangePasswordRequest $request): JsonResponse
    {
        $this->passwords->change(
            $request->user(),
            $request->string('password')->toString(),
        );

        return ApiResponse::success('Password changed successfully. Other sessions have been signed out.');
    }
}
