<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Fixed worker and client accounts used while developing the frontend.
 * Never runs in production.
 */
class DevelopmentUserSeeder extends Seeder
{
    private const PASSWORD = 'Password123';

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('Skipping DevelopmentUserSeeder in production.');

            return;
        }

        $this->seed('worker@micro-task.test', fn () => User::factory()->worker());
        $this->seed('client@micro-task.test', fn () => User::factory()->client());

        $this->command?->info('Development accounts ready (password: '.self::PASSWORD.').');
    }

    private function seed(string $email, callable $factory): void
    {
        if (User::withTrashed()->where('email', $email)->exists()) {
            return;
        }

        $factory()->create([
            'email' => $email,
            'password' => self::PASSWORD,
            'status' => UserStatus::Active,
            'email_verified_at' => now(),
        ]);
    }
}
