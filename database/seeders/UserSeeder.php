<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Creates the platform administrators.
 *
 * Admins are never publicly registerable, so this seeder and the
 * `admin:create` command are the only automated ways to mint one.
 */
class UserSeeder extends Seeder
{
    /**
     * The admin roster. Everything identifying lives here; only the password
     * is indirected, through config/seeding.php, so that no real credential
     * is committed. `password_key` resolves to ADMIN_<KEY>_PASSWORD.
     *
     * @var list<array{first_name: string, last_name: string, email: string, country_code: string, password_key: string}>
     */
    private const ADMINS = [
        [
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'admin@cloudleven.com',
            'country_code' => 'LK',
            'password_key' => 'super',
        ],
        [
            'first_name' => 'Adithya',
            'last_name' => 'Webappclouds',
            'email' => 'adithya@cloudleven.com',
            'country_code' => 'LK',
            'password_key' => 'adithya',
        ],
        [
            'first_name' => 'Lahiru',
            'last_name' => 'Admin',
            'email' => 'lahiru@cloudleven.com',
            'country_code' => 'LK',
            'password_key' => 'lahiru',
        ],
    ];

    public function run(): void
    {
        foreach (self::ADMINS as $admin) {
            $this->seedAdmin($admin);
        }
    }

    /**
     * @param  array{first_name: string, last_name: string, email: string, country_code: string, password_key: string}  $admin
     */
    private function seedAdmin(array $admin): void
    {
        $email = mb_strtolower($admin['email']);

        // withTrashed: a soft-deleted admin still owns the address, and the
        // unique index would reject a second row for it.
        $user = User::withTrashed()->firstOrNew(['email' => $email]);

        if ($user->exists) {
            // Names are refreshed, but the password, role and status are left
            // alone: re-running the seeder must not reset a rotated password
            // or quietly reactivate an account somebody suspended on purpose.
            $user->fill([
                'first_name' => $admin['first_name'],
                'last_name' => $admin['last_name'],
            ])->save();

            $this->command?->info("Admin already exists, name refreshed: {$email}");

            return;
        }

        $password = (string) config("seeding.admin_passwords.{$admin['password_key']}");

        if ($password === '') {
            $this->command?->warn("Skipping {$email}: ".self::envKey($admin['password_key']).' is not set.');

            return;
        }

        $user->fill([
            'first_name' => $admin['first_name'],
            'last_name' => $admin['last_name'],
            'password' => $password,
            'country_code' => $admin['country_code'],
            'preferred_language' => 'en',
            'terms_accepted_at' => now(),
        ]);

        // Assigned explicitly because they are guarded against mass assignment.
        $user->role = UserRole::Admin;
        $user->status = UserStatus::Active;
        $user->email_verified_at = now();
        $user->save();

        $this->command?->info("Admin account created: {$email}");
    }

    private static function envKey(string $passwordKey): string
    {
        return 'ADMIN_'.mb_strtoupper($passwordKey).'_PASSWORD';
    }
}
