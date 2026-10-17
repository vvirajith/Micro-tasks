<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Http\Controllers\Api\Auth\EmailVerificationController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\MeController;
use App\Http\Controllers\Api\Auth\PasswordController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\WorkspaceController;
use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return [
        'status' => 'ok',
        'app' => config('app.name'),
        'environment' => app()->environment(),
        'laravel' => app()->version(),
        'php' => PHP_VERSION,
        'time' => now()->toIso8601String(),
    ];
});

/*
|--------------------------------------------------------------------------
| Guest authentication routes
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->name('auth.')->group(function (): void {
    Route::post('register/worker', [RegisterController::class, 'worker'])
        ->middleware('throttle:register')
        ->name('register.worker');

    Route::post('register/client', [RegisterController::class, 'client'])
        ->middleware('throttle:register')
        ->name('register.client');

    Route::post('login', LoginController::class)
        ->middleware('throttle:login')
        ->name('login');

    Route::post('forgot-password', [PasswordController::class, 'forgot'])
        ->middleware('throttle:password-reset')
        ->name('password.forgot');

    Route::post('reset-password', [PasswordController::class, 'reset'])
        ->middleware('throttle:password-reset')
        ->name('password.reset');
});

/*
| Opened from the email client, so the link carries a signature instead of a
| token. The signature is checked inside the controller rather than by the
| `signed` middleware so that a browser gets redirected back to the frontend
| instead of a raw JSON error.
|
| The route name must stay `verification.verify`: that is what Laravel's
| VerifyEmail notification signs.
*/
Route::get('auth/verify-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware('throttle:verification')
    ->name('verification.verify');

/*
|--------------------------------------------------------------------------
| Authenticated routes
|--------------------------------------------------------------------------
|
| `account.active` blocks suspended and blocked accounts in one place.
| Email verification is not required here: a pending user still needs to read
| their own profile and request a new verification link.
|
*/

Route::middleware(['auth:sanctum', 'account.active'])->group(function (): void {
    Route::prefix('auth')->name('auth.')->group(function (): void {
        Route::get('me', MeController::class)->name('me');

        Route::post('logout', LogoutController::class)->name('logout');
        Route::post('logout/all', [LogoutController::class, 'all'])->name('logout.all');

        Route::put('change-password', [PasswordController::class, 'change'])
            ->middleware('throttle:password-reset')
            ->name('password.change');

        Route::post('email/verification-notification', [EmailVerificationController::class, 'resend'])
            ->middleware('throttle:verification')
            ->name('verification.send');
    });

    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');

    /*
    |----------------------------------------------------------------------
    | Role-scoped areas (verified email required)
    |----------------------------------------------------------------------
    |
    | Future task, wallet, campaign and admin modules mount inside these
    | groups and inherit the role and verification guards automatically.
    |
    */

    Route::middleware('verified')->group(function (): void {
        Route::prefix('worker')->name('worker.')
            ->middleware(EnsureUserHasRole::using(UserRole::Worker))
            ->group(function (): void {
                Route::get('/', WorkspaceController::class)->name('workspace');
            });

        Route::prefix('client')->name('client.')
            ->middleware(EnsureUserHasRole::using(UserRole::Client))
            ->group(function (): void {
                Route::get('/', WorkspaceController::class)->name('workspace');
            });

        Route::prefix('admin')->name('admin.')
            ->middleware(EnsureUserHasRole::using(UserRole::Admin))
            ->group(function (): void {
                Route::get('/', WorkspaceController::class)->name('workspace');
            });
    });
});
