<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password as promptPassword;

/**
 * The only interactive way to create an admin. Admin accounts are intentionally
 * absent from the public registration endpoints.
 */
class CreateAdminUser extends Command
{
    protected $signature = 'admin:create
                            {--email= : Email address for the admin account}
                            {--first-name= : First name}
                            {--last-name= : Last name}
                            {--country=US : ISO 3166-1 alpha-2 country code}';

    protected $description = 'Create an administrator account';

    public function handle(): int
    {
        $email = mb_strtolower(trim((string) ($this->option('email') ?: $this->ask('Email address'))));
        $firstName = (string) ($this->option('first-name') ?: $this->ask('First name'));
        $lastName = (string) ($this->option('last-name') ?: $this->ask('Last name'));
        $country = mb_strtoupper((string) $this->option('country'));

        // Read interactively so the password never lands in the shell history.
        $password = promptPassword('Password', required: true);

        $validator = Validator::make([
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'country_code' => $country,
            'password' => $password,
        ], [
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'first_name' => ['required', 'string', 'min:2', 'max:100'],
            'last_name' => ['required', 'string', 'min:2', 'max:100'],
            'country_code' => ['required', 'string', 'size:2', 'alpha'],
            'password' => ['required', 'string', Password::defaults()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = new User([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'password' => $password,
            'country_code' => $country,
            'preferred_language' => 'en',
            'terms_accepted_at' => now(),
        ]);

        $user->role = UserRole::Admin;
        $user->status = UserStatus::Active;
        $user->email_verified_at = now();
        $user->save();

        $this->info("Admin account created for {$email}.");

        return self::SUCCESS;
    }
}
