<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Database\Seeders\DevelopmentUserSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_public_route_can_create_an_admin(): void
    {
        $adminCreatingRoutes = collect(Route::getRoutes())
            ->filter(fn ($route) => str_contains($route->uri(), 'register'))
            ->map(fn ($route) => $route->uri())
            ->values();

        $this->assertEqualsCanonicalizing(
            ['api/auth/register/worker', 'api/auth/register/client'],
            $adminCreatingRoutes->all(),
        );
    }

    public function test_the_registration_endpoints_cannot_be_coerced_into_making_an_admin(): void
    {
        $this->postJson(route('auth.register.worker'), [
            'first_name' => 'Mallory',
            'last_name' => 'Attacker',
            'email' => 'mallory@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'country_code' => 'US',
            'preferred_language' => 'en',
            'terms_accepted' => true,
            'role' => UserRole::Admin->value,
        ])->assertCreated();

        $this->assertSame(0, User::query()->role(UserRole::Admin)->count());
    }

    public function test_the_seeder_creates_every_admin_in_the_roster(): void
    {
        $this->seed(UserSeeder::class);

        $this->assertSame(3, User::query()->role(UserRole::Admin)->count());

        $admin = User::whereEmail('admin@cloudleven.com')->firstOrFail();

        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertSame(UserStatus::Active, $admin->status);
        $this->assertTrue($admin->hasVerifiedEmail());
        $this->assertNotSame(config('seeding.admin_passwords.super'), $admin->password);
    }

    public function test_the_seeder_is_idempotent(): void
    {
        $this->seed(UserSeeder::class);
        $this->seed(UserSeeder::class);

        $this->assertSame(3, User::query()->role(UserRole::Admin)->count());
    }

    public function test_reseeding_keeps_a_rotated_password_and_a_suspension(): void
    {
        $this->seed(UserSeeder::class);

        $admin = User::whereEmail('admin@cloudleven.com')->firstOrFail();
        $admin->forceFill([
            'password' => 'Rotated@9876',
            'status' => UserStatus::Suspended,
        ])->save();

        $this->seed(UserSeeder::class);

        $admin->refresh();

        $this->assertSame(UserStatus::Suspended, $admin->status);
        $this->assertTrue(Hash::check('Rotated@9876', $admin->password));
    }

    public function test_an_admin_without_a_configured_password_is_skipped(): void
    {
        config(['seeding.admin_passwords' => ['super' => '', 'adithya' => '', 'lahiru' => '']]);

        $this->seed(UserSeeder::class);

        $this->assertSame(0, User::query()->role(UserRole::Admin)->count());
    }

    public function test_the_development_seeder_creates_a_worker_and_a_client(): void
    {
        $this->seed(DevelopmentUserSeeder::class);

        $this->assertSame(1, User::query()->role(UserRole::Worker)->count());
        $this->assertSame(1, User::query()->role(UserRole::Client)->count());
        $this->assertDatabaseCount('client_profiles', 1);
    }

    public function test_the_admin_command_creates_an_administrator(): void
    {
        $this->artisan('admin:create', [
            '--email' => 'root@micro-task.test',
            '--first-name' => 'Root',
            '--last-name' => 'Operator',
            '--country' => 'US',
        ])
            ->expectsQuestion('Password', 'SuperSecret123')
            ->assertSuccessful();

        $admin = User::whereEmail('root@micro-task.test')->firstOrFail();

        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertTrue($admin->hasVerifiedEmail());
    }

    public function test_the_admin_command_rejects_a_duplicate_email(): void
    {
        User::factory()->admin()->create(['email' => 'root@micro-task.test']);

        $this->artisan('admin:create', [
            '--email' => 'root@micro-task.test',
            '--first-name' => 'Root',
            '--last-name' => 'Operator',
        ])
            ->expectsQuestion('Password', 'SuperSecret123')
            ->assertFailed();
    }
}
