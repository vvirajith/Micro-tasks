<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password = null;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('Password123'),
            'role' => UserRole::Worker,
            'status' => UserStatus::Active,
            'country_code' => fake()->randomElement(['US', 'GB', 'IN', 'BR', 'NG', 'PH']),
            'preferred_language' => 'en',
            'terms_accepted_at' => now(),
            'last_login_at' => null,
            'remember_token' => Str::random(10),
        ];
    }

    public function worker(): static
    {
        return $this->state(['role' => UserRole::Worker]);
    }

    public function client(): static
    {
        return $this->state(['role' => UserRole::Client])
            ->has(ClientProfileFactory::new(), 'clientProfile');
    }

    public function admin(): static
    {
        return $this->state(['role' => UserRole::Admin]);
    }

    public function status(UserStatus $status): static
    {
        return $this->state(['status' => $status]);
    }

    public function unverified(): static
    {
        return $this->state(['email_verified_at' => null]);
    }
}
