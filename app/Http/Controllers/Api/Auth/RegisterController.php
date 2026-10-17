<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterClientRequest;
use App\Http\Requests\Auth\RegisterWorkerRequest;
use App\Http\Resources\UserResource;
use App\Services\Auth\RegistrationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    public function __construct(private readonly RegistrationService $registration) {}

    public function worker(RegisterWorkerRequest $request): JsonResponse
    {
        $user = $this->registration->registerWorker($request->validated());

        return ApiResponse::created(
            'Registration successful. Please check your email to verify your account.',
            ['user' => UserResource::make($user)],
        );
    }

    public function client(RegisterClientRequest $request): JsonResponse
    {
        $user = $this->registration->registerClient($request->validated());

        return ApiResponse::created(
            'Registration successful. Please check your email to verify your account.',
            ['user' => UserResource::make($user)],
        );
    }
}
