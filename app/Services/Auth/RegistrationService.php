<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;

/**
 * Creates accounts for the publicly registerable roles.
 *
 * Deliberately knows nothing about wallets, coins or rewards. The `Registered`
 * event is the extension point a future wallet module should listen on.
 */
class RegistrationService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function registerWorker(array $data): User
    {
        $user = DB::transaction(fn (): User => $this->createUser($data, UserRole::Worker));

        event(new Registered($user));

        return $user;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function registerClient(array $data): User
    {
        $user = DB::transaction(function () use ($data): User {
            $user = $this->createUser($data, UserRole::Client);

            $user->clientProfile()->create([
                'company_name' => $data['company_name'],
                'phone' => $data['phone'] ?? null,
            ]);

            return $user->load('clientProfile');
        });

        event(new Registered($user));

        return $user;
    }

    /**
     * `role` and `status` are guarded on the model, so they are assigned
     * explicitly here rather than being mass-assigned from request input.
     *
     * @param  array<string, mixed>  $data
     */
    private function createUser(array $data, UserRole $role): User
    {
        $user = new User([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'country_code' => $data['country_code'],
            'preferred_language' => $data['preferred_language'] ?? 'en',
            'terms_accepted_at' => now(),
        ]);

        $user->role = $role;
        $user->status = UserStatus::Active;
        $user->save();

        return $user;
    }
}
