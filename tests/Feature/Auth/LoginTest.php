<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_log_in_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'worker@example.com',
            'password' => 'Password123',
        ]);

        $response = $this->postJson(route('auth.login'), [
            'email' => 'worker@example.com',
            'password' => 'Password123',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Login successful')
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonStructure(['data' => ['user' => ['id', 'email', 'role', 'status'], 'token']]);

        $this->assertNotEmpty($response->json('data.token'));
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_logging_in_records_the_last_login_timestamp(): void
    {
        $user = User::factory()->create([
            'email' => 'worker@example.com',
            'password' => 'Password123',
            'last_login_at' => null,
        ]);

        $this->postJson(route('auth.login'), [
            'email' => 'worker@example.com',
            'password' => 'Password123',
        ])->assertOk();

        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_the_response_never_contains_sensitive_fields(): void
    {
        User::factory()->create([
            'email' => 'worker@example.com',
            'password' => 'Password123',
        ]);

        $user = $this->postJson(route('auth.login'), [
            'email' => 'worker@example.com',
            'password' => 'Password123',
        ])->json('data.user');

        $this->assertArrayNotHasKey('password', $user);
        $this->assertArrayNotHasKey('remember_token', $user);
        $this->assertArrayNotHasKey('deleted_at', $user);
    }

    public function test_login_fails_with_the_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'worker@example.com',
            'password' => 'Password123',
        ]);

        $this->postJson(route('auth.login'), [
            'email' => 'worker@example.com',
            'password' => 'WrongPassword123',
        ])
            ->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'The provided credentials are incorrect.');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_an_unknown_email_returns_the_same_error_as_a_wrong_password(): void
    {
        $this->postJson(route('auth.login'), [
            'email' => 'nobody@example.com',
            'password' => 'Password123',
        ])
            ->assertStatus(401)
            ->assertJsonPath('message', 'The provided credentials are incorrect.');
    }

    public function test_a_blocked_user_cannot_log_in(): void
    {
        User::factory()->status(UserStatus::Blocked)->create([
            'email' => 'blocked@example.com',
            'password' => 'Password123',
        ]);

        $this->postJson(route('auth.login'), [
            'email' => 'blocked@example.com',
            'password' => 'Password123',
        ])
            ->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', UserStatus::Blocked->denialMessage());

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_a_suspended_user_cannot_log_in(): void
    {
        User::factory()->status(UserStatus::Suspended)->create([
            'email' => 'suspended@example.com',
            'password' => 'Password123',
        ]);

        $this->postJson(route('auth.login'), [
            'email' => 'suspended@example.com',
            'password' => 'Password123',
        ])
            ->assertStatus(403)
            ->assertJsonPath('message', UserStatus::Suspended->denialMessage());
    }

    public function test_a_pending_user_can_log_in(): void
    {
        User::factory()->status(UserStatus::Pending)->unverified()->create([
            'email' => 'pending@example.com',
            'password' => 'Password123',
        ]);

        $this->postJson(route('auth.login'), [
            'email' => 'pending@example.com',
            'password' => 'Password123',
        ])
            ->assertOk()
            ->assertJsonPath('data.user.email_verified', false);
    }

    public function test_a_soft_deleted_user_cannot_log_in(): void
    {
        $user = User::factory()->create([
            'email' => 'gone@example.com',
            'password' => 'Password123',
        ]);
        $user->delete();

        $this->postJson(route('auth.login'), [
            'email' => 'gone@example.com',
            'password' => 'Password123',
        ])->assertStatus(401);
    }

    public function test_login_requires_an_email_and_password(): void
    {
        $this->postJson(route('auth.login'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_attempts_are_rate_limited(): void
    {
        User::factory()->create([
            'email' => 'worker@example.com',
            'password' => 'Password123',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson(route('auth.login'), [
                'email' => 'worker@example.com',
                'password' => 'WrongPassword123',
            ])->assertStatus(401);
        }

        $this->postJson(route('auth.login'), [
            'email' => 'worker@example.com',
            'password' => 'Password123',
        ])
            ->assertStatus(429)
            ->assertJsonPath('success', false);
    }

    public function test_a_client_receives_their_company_profile_on_login(): void
    {
        User::factory()->client()->create([
            'email' => 'client@example.com',
            'password' => 'Password123',
        ]);

        $this->postJson(route('auth.login'), [
            'email' => 'client@example.com',
            'password' => 'Password123',
        ])
            ->assertOk()
            ->assertJsonStructure(['data' => ['user' => ['client_profile' => ['company_name']]]]);
    }
}
