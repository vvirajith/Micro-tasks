<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureModels();
        $this->configurePasswordRules();
        $this->configureRateLimiting();
        $this->configureNotificationUrls();
        $this->configureEventListeners();
    }

    /**
     * The slim Laravel 12 skeleton ships no EventServiceProvider, so the
     * listener that mails the verification link is registered here.
     */
    private function configureEventListeners(): void
    {
        Event::listen(Registered::class, SendEmailVerificationNotification::class);
    }

    private function configureModels(): void
    {
        // Fail loudly on lazy loading and on writes to guarded attributes
        // instead of silently producing N+1 queries or dropping data.
        Model::shouldBeStrict(! $this->app->isProduction());
        Model::unguard(false);
    }

    private function configurePasswordRules(): void
    {
        Password::defaults(function (): Password {
            $rule = Password::min(8)->letters()->mixedCase()->numbers();

            // The breach check calls an external API, so it is production only.
            return $this->app->isProduction()
                ? $rule->symbols()->uncompromised()
                : $rule;
        });
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)
            ->by($request->user()?->getAuthIdentifier() ?: $request->ip()));

        // Keyed by email *and* IP so one attacker cannot lock out a real user
        // by hammering their address from elsewhere.
        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(5)->by(Str::lower((string) $request->input('email')).'|'.$request->ip()),
            Limit::perMinute(20)->by($request->ip()),
        ]);

        RateLimiter::for('register', fn (Request $request) => Limit::perHour(10)->by($request->ip()));

        RateLimiter::for('password-reset', fn (Request $request) => [
            Limit::perHour(5)->by(Str::lower((string) $request->input('email')).'|'.$request->ip()),
            Limit::perHour(15)->by($request->ip()),
        ]);

        RateLimiter::for('verification', fn (Request $request) => Limit::perHour(6)
            ->by($request->user()?->getAuthIdentifier() ?: $request->ip()));
    }

    /**
     * Password resets are completed in the Next.js app, so the emailed link
     * must point at the frontend rather than at an API route.
     */
    private function configureNotificationUrls(): void
    {
        ResetPassword::createUrlUsing(fn (User $user, string $token): string => sprintf(
            '%s/auth/reset-password?token=%s&email=%s',
            rtrim((string) config('app.frontend_url'), '/'),
            $token,
            urlencode($user->getEmailForPasswordReset()),
        ));
    }
}
