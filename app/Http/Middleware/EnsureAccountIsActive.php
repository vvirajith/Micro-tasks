<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use App\Models\User;
use App\Services\Auth\AuthenticationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Single enforcement point for account-status rules on authenticated requests.
 *
 * The rules themselves live on UserStatus, so they are never re-implemented in
 * controllers or services.
 */
class EnsureAccountIsActive
{
    public function __construct(private readonly AuthenticationService $authentication) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && ! $user->status->canAccessProtectedRoutes()) {
            if ($user->status->shouldRevokeTokens()) {
                $this->authentication->revokeAllTokens($user);
            }

            throw ApiException::accountUnavailable($user->status);
        }

        return $next($request);
    }
}
