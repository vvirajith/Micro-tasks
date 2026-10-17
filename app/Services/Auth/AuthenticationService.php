<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Exceptions\ApiException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\NewAccessToken;

class AuthenticationService
{
    /**
     * Verify credentials and issue a Sanctum token.
     *
     * @return array{user: User, token: string}
     *
     * @throws ApiException
     */
    public function authenticate(string $email, string $password, string $deviceName): array
    {
        $user = User::where('email', $email)->first();

        // Hash::check against a dummy value keeps the response time roughly
        // constant whether or not the address exists.
        if (! $user || ! Hash::check($password, $user->password)) {
            if (! $user) {
                Hash::make($password);
            }

            throw ApiException::invalidCredentials();
        }

        if (! $user->status->canLogin()) {
            throw ApiException::accountUnavailable($user->status);
        }

        $token = $this->issueToken($user, $deviceName);

        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        if ($user->isClient()) {
            $user->load('clientProfile');
        }

        return [
            'user' => $user,
            'token' => $token->plainTextToken,
        ];
    }

    public function issueToken(User $user, string $deviceName): NewAccessToken
    {
        return $user->createToken($deviceName);
    }

    /**
     * Revoke only the token used for the current request.
     */
    public function revokeCurrentToken(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }

    /**
     * Revoke every token, e.g. after a password change or a block.
     */
    public function revokeAllTokens(User $user): void
    {
        $user->tokens()->delete();
    }
}
