<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\User;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Usage: ->middleware(EnsureUserHasRole::using(UserRole::Admin))
 *
 * Role names are resolved through the enum, so a typo fails loudly at boot
 * rather than silently granting or denying access at runtime.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            throw new AuthenticationException(guards: [Auth::getDefaultDriver()]);
        }

        $allowed = array_map(
            static fn (string $role): UserRole => UserRole::from($role),
            $roles,
        );

        if (! $user->hasRole(...$allowed)) {
            throw new AccessDeniedHttpException(
                'This action is unauthorized for your account role.',
            );
        }

        return $next($request);
    }

    /**
     * Builds the middleware string for route definitions.
     */
    public static function using(UserRole ...$roles): string
    {
        return 'role:'.implode(',', array_column($roles, 'value'));
    }
}
