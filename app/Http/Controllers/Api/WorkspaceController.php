<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Entry point of each role-scoped area. The future worker, client and admin
 * modules mount under the same guarded route groups.
 */
class WorkspaceController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        return ApiResponse::success('Workspace context retrieved', [
            'role' => $user->role->value,
            'status' => $user->status->value,
            'email_verified' => $user->hasVerifiedEmail(),
        ]);
    }
}
