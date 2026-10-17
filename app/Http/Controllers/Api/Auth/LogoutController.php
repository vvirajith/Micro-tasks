<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\AuthenticationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    public function __construct(private readonly AuthenticationService $authentication) {}

    /**
     * Revokes the token that authenticated this request.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $this->authentication->revokeCurrentToken($request->user());

        return ApiResponse::success('Logout successful');
    }

    /**
     * Revokes every token belonging to the user.
     */
    public function all(Request $request): JsonResponse
    {
        $this->authentication->revokeAllTokens($request->user());

        return ApiResponse::success('Logged out of all devices');
    }
}
