<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API flavour of Laravel's verified middleware: it always answers with the
 * project's JSON envelope instead of redirecting to a web route.
 */
class EnsureEmailIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof MustVerifyEmail || ! $user->hasVerifiedEmail()) {
            throw ApiException::emailNotVerified();
        }

        return $next($request);
    }
}
