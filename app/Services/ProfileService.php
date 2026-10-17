<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProfileService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $user->fill(array_intersect_key($data, array_flip([
                'first_name',
                'last_name',
                'country_code',
                'preferred_language',
            ])))->save();

            $profileData = array_intersect_key($data, array_flip(['company_name', 'phone']));

            if ($user->isClient() && $profileData !== []) {
                $user->clientProfile()->updateOrCreate([], $profileData);
            }

            if ($user->isClient()) {
                $user->load('clientProfile');
            }

            return $user;
        });
    }
}
