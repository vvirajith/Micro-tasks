<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class WorkerRegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'country_code' => 'GB',
            'preferred_language' => 'en',
            'terms_accepted' => true,
        ], $overrides);
    }

    public function test_a_worker_can_register(): void
    {
        Notification::fake();

        $response = $this->postJson(route('auth.register.worker'), $this->payload());

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'ada@example.com')
            ->assertJsonPath('data.user.role', UserRole::Worker->value)
            ->assertJsonPath('data.user.status', UserStatus::Active->value)
            ->assertJsonPath('data.user.email_verified', false);

        $user = User::whereEmail('ada@example.com')->firstOrFail();

        $this->assertSame(UserRole::Worker, $user->role);
        $this->assertSame(UserStatus::Active, $user->status);
        $this->assertNull($user->email_verified_at);
        $this->assertNotNull($user->terms_accepted_at);
        $this->assertNull($user->clientProfile()->first());
    }

    public function test_the_password_is_hashed_and_never_returned(): void
    {
        Notification::fake();

        $response = $this->postJson(route('auth.register.worker'), $this->payload());

        $response->assertCreated();

        $user = User::whereEmail('ada@example.com')->firstOrFail();

        $this->assertNotSame('Password123', $user->password);
        $this->assertTrue(password_verify('Password123', $user->password));

        $json = $response->json('data.user');
        $this->assertArrayNotHasKey('password', $json);
        $this->assertArrayNotHasKey('remember_token', $json);
    }

    public function test_a_verification_email_is_sent_after_registration(): void
    {
        Notification::fake();

        $this->postJson(route('auth.register.worker'), $this->payload())->assertCreated();

        Notification::assertSentTo(
            User::whereEmail('ada@example.com')->firstOrFail(),
            VerifyEmail::class,
        );
    }

    public function test_registration_rejects_a_duplicate_email(): void
    {
        Notification::fake();
        User::factory()->create(['email' => 'ada@example.com']);

        $this->postJson(route('auth.register.worker'), $this->payload())
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation failed')
            ->assertJsonValidationErrors('email');
    }

    public function test_the_email_is_matched_case_insensitively(): void
    {
        Notification::fake();
        User::factory()->create(['email' => 'ada@example.com']);

        $this->postJson(route('auth.register.worker'), $this->payload(['email' => 'ADA@Example.com']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_registration_rejects_invalid_data(): void
    {
        $this->postJson(route('auth.register.worker'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'first_name',
                'last_name',
                'email',
                'password',
                'country_code',
                'preferred_language',
                'terms_accepted',
            ]);
    }

    public function test_registration_requires_a_matching_password_confirmation(): void
    {
        $this->postJson(route('auth.register.worker'), $this->payload([
            'password_confirmation' => 'SomethingElse123',
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    public function test_registration_requires_a_strong_password(): void
    {
        $this->postJson(route('auth.register.worker'), $this->payload([
            'password' => 'secret',
            'password_confirmation' => 'secret',
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    public function test_registration_requires_accepting_the_terms(): void
    {
        $this->postJson(route('auth.register.worker'), $this->payload(['terms_accepted' => false]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('terms_accepted');
    }

    public function test_the_role_cannot_be_escalated_through_mass_assignment(): void
    {
        Notification::fake();

        $this->postJson(route('auth.register.worker'), $this->payload([
            'role' => UserRole::Admin->value,
            'status' => UserStatus::Suspended->value,
        ]))->assertCreated();

        $user = User::whereEmail('ada@example.com')->firstOrFail();

        $this->assertSame(UserRole::Worker, $user->role);
        $this->assertSame(UserStatus::Active, $user->status);
    }

    public function test_registration_is_rate_limited(): void
    {
        Notification::fake();

        for ($i = 0; $i < 10; $i++) {
            $this->postJson(route('auth.register.worker'), $this->payload([
                'email' => "worker{$i}@example.com",
            ]))->assertCreated();
        }

        $this->postJson(route('auth.register.worker'), $this->payload(['email' => 'overflow@example.com']))
            ->assertStatus(429)
            ->assertJsonPath('success', false);
    }
}
