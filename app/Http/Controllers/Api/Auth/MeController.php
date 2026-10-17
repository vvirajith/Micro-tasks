<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isClient()) {
            $user->loadMissing('clientProfile');
        }

        return ApiResponse::success('Authenticated user retrieved', [
            'user' => UserResource::make($user),
        ]);
    }
}
