<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private const GENERIC_MESSAGE = 'If an account matches that email address, a password reset link has been sent.';

    public function test_a_reset_link_is_emailed_to_a_known_address(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'worker@example.com']);

        $this->postJson(route('auth.password.forgot'), ['email' => 'worker@example.com'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', self::GENERIC_MESSAGE);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_an_unknown_address_gets_the_same_response(): void
    {
        Notification::fake();

        $this->postJson(route('auth.password.forgot'), ['email' => 'nobody@example.com'])
            ->assertOk()
            ->assertJsonPath('message', self::GENERIC_MESSAGE);

        Notification::assertNothingSent();
    }

    public function test_the_reset_link_points_at_the_frontend(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'worker@example.com']);

        $this->postJson(route('auth.password.forgot'), ['email' => 'worker@example.com'])->assertOk();

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $url = $notification->toMail($user)->actionUrl;

            return str_starts_with($url, config('app.frontend_url').'/auth/reset-password?token=')
                && str_contains($url, 'email='.urlencode($user->email));
        });
    }

    public function test_forgot_password_requires_a_valid_email(): void
    {
        $this->postJson(route('auth.password.forgot'), ['email' => 'not-an-email'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_forgot_password_is_rate_limited(): void
    {
        Notification::fake();
        User::factory()->create(['email' => 'worker@example.com']);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson(route('auth.password.forgot'), ['email' => 'worker@example.com'])->assertOk();
        }

        $this->postJson(route('auth.password.forgot'), ['email' => 'worker@example.com'])
            ->assertStatus(429);
    }

    public function test_a_password_can_be_reset_with_a_valid_token(): void
    {
        Notification::fake();
        $user = User::factory()->create([
            'email' => 'worker@example.com',
            'password' => 'Password123',
        ]);

        $this->postJson(route('auth.password.forgot'), ['email' => 'worker@example.com'])->assertOk();

        $token = $this->capturedToken($user);

        $this->postJson(route('auth.password.reset'), [
            'token' => $token,
            'email' => 'worker@example.com',
            'password' => 'NewPassword456',
            'password_confirmation' => 'NewPassword456',
        ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertTrue(Hash::check('NewPassword456', $user->fresh()->password));
    }

    public function test_resetting_a_password_revokes_every_existing_token(): void
    {
        Notification::fake();
        $user = User::factory()->create([
            'email' => 'worker@example.com',
            'password' => 'Password123',
        ]);
        $user->createToken('old-device');

        $this->postJson(route('auth.password.forgot'), ['email' => 'worker@example.com'])->assertOk();

        $this->postJson(route('auth.password.reset'), [
            'token' => $this->capturedToken($user),
            'email' => 'worker@example.com',
            'password' => 'NewPassword456',
            'password_confirmation' => 'NewPassword456',
        ])->assertOk();

        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_an_invalid_token_is_rejected(): void
    {
        User::factory()->create(['email' => 'worker@example.com']);

        $this->postJson(route('auth.password.reset'), [
            'token' => 'completely-invalid-token',
            'email' => 'worker@example.com',
            'password' => 'NewPassword456',
            'password_confirmation' => 'NewPassword456',
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors('email');
    }

    public function test_an_expired_token_is_rejected(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'worker@example.com']);

        $this->postJson(route('auth.password.forgot'), ['email' => 'worker@example.com'])->assertOk();
        $token = $this->capturedToken($user);

        $this->travel(config('auth.passwords.users.expire') + 1)->minutes();

        $this->postJson(route('auth.password.reset'), [
            'token' => $token,
            'email' => 'worker@example.com',
            'password' => 'NewPassword456',
            'password_confirmation' => 'NewPassword456',
        ])->assertStatus(422);
    }

    public function test_the_new_password_must_be_confirmed_and_strong(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'worker@example.com']);

        $this->postJson(route('auth.password.forgot'), ['email' => 'worker@example.com'])->assertOk();
        $token = $this->capturedToken($user);

        $this->postJson(route('auth.password.reset'), [
            'token' => $token,
            'email' => 'worker@example.com',
            'password' => 'weak',
            'password_confirmation' => 'different',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    private function capturedToken(User $user): string
    {
        $token = null;

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token) {
            $token = $notification->token;

            return true;
        });

        return (string) $token;
    }
}
