<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\Auth\AuthenticationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class LoginController extends Controller
{
    public function __construct(private readonly AuthenticationService $authentication) {}

    public function __invoke(LoginRequest $request): JsonResponse
    {
        ['user' => $user, 'token' => $token] = $this->authentication->authenticate(
            $request->string('email')->toString(),
            $request->string('password')->toString(),
            $request->deviceName(),
        );

        return ApiResponse::success('Login successful', [
            'user' => UserResource::make($user),
            'token' => $token,
        ]);
    }
}
