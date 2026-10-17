<?php

use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Http\Middleware\EnsureUserHasRole;
use App\Support\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Baseline throttle for every API route; the auth endpoints add their
        // own stricter limiters on top.
        $middleware->throttleApi();

        // Laravel points guests at route('login') by default, which does not
        // exist in an API-only app: resolving it throws inside the auth
        // middleware, so an unauthenticated non-JSON request would surface as
        // a 500 instead of a 401. API callers are still answered by the
        // handler below; this only gives browsers somewhere to land.
        $middleware->redirectGuestsTo(fn (): string => rtrim((string) config('app.frontend_url'), '/').'/login');

        $middleware->alias([
            'account.active' => EnsureAccountIsActive::class,
            'role' => EnsureUserHasRole::class,
            'verified' => EnsureEmailIsVerified::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Every API failure is rendered through the same envelope, and internal
        // details never reach the client outside of debug mode.
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return match (true) {
                $e instanceof ValidationException => ApiResponse::error(
                    'Validation failed',
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                    $e->errors(),
                ),

                $e instanceof AuthenticationException => ApiResponse::error(
                    'Unauthenticated.',
                    Response::HTTP_UNAUTHORIZED,
                ),

                $e instanceof NotFoundHttpException => ApiResponse::error(
                    'The requested resource was not found.',
                    Response::HTTP_NOT_FOUND,
                ),

                $e instanceof TooManyRequestsHttpException => ApiResponse::error(
                    'Too many attempts. Please try again later.',
                    Response::HTTP_TOO_MANY_REQUESTS,
                ),

                $e instanceof HttpExceptionInterface => ApiResponse::error(
                    $e->getMessage() ?: 'Request failed.',
                    $e->getStatusCode(),
                ),

                default => ApiResponse::error(
                    config('app.debug')
                        ? $e->getMessage()
                        : 'An unexpected error occurred. Please try again later.',
                    Response::HTTP_INTERNAL_SERVER_ERROR,
                ),
            };
        });

        // Passwords and tokens must never reach the log files.
        $exceptions->dontFlash([
            'current_password',
            'password',
            'password_confirmation',
            'token',
        ]);
    })->create();
