<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordService
{
    public function __construct(private readonly AuthenticationService $authentication) {}

    /**
     * Always returns the broker status; callers must not branch on it in a way
     * that reveals whether the address is registered.
     */
    public function sendResetLink(string $email): string
    {
        return Password::sendResetLink(['email' => $email]);
    }

    /**
     * @param  array<string, string>  $credentials
     */
    public function reset(array $credentials): string
    {
        return Password::reset($credentials, function (User $user, string $password): void {
            $user->forceFill([
                'password' => $password,
                'remember_token' => Str::random(60),
            ])->save();

            // A reset implies the previous password may be compromised, so every
            // existing session is terminated.
            $this->authentication->revokeAllTokens($user);

            event(new PasswordReset($user));
        });
    }

    /**
     * Changes the password for an authenticated user and keeps only the token
     * used for the current request alive.
     */
    public function change(User $user, string $newPassword): void
    {
        $currentTokenId = $user->currentAccessToken()?->getKey();

        $user->forceFill(['password' => $newPassword])->save();

        $user->tokens()
            ->when($currentTokenId !== null, fn ($query) => $query->whereKeyNot($currentTokenId))
            ->delete();
    }
}
