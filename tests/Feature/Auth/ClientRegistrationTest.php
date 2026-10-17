<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\ClientProfile;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ClientRegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Grace',
            'last_name' => 'Hopper',
            'email' => 'grace@acme-corp.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'company_name' => 'Acme Corporation',
            'country_code' => 'US',
            'phone' => '+15551234567',
            'terms_accepted' => true,
        ], $overrides);
    }

    public function test_a_client_can_register_with_a_company_profile(): void
    {
        Notification::fake();

        $response = $this->postJson(route('auth.register.client'), $this->payload());

        $response->assertCreated()
            ->assertJsonPath('data.user.role', UserRole::Client->value)
            ->assertJsonPath('data.user.status', UserStatus::Active->value)
            ->assertJsonPath('data.user.client_profile.company_name', 'Acme Corporation')
            ->assertJsonPath('data.user.client_profile.phone', '+15551234567');

        $user = User::whereEmail('grace@acme-corp.com')->firstOrFail();

        $this->assertDatabaseHas('client_profiles', [
            'user_id' => $user->id,
            'company_name' => 'Acme Corporation',
        ]);

        // Company data must not leak into the users table.
        $this->assertFalse(array_key_exists('company_name', $user->getAttributes()));
    }

    public function test_the_phone_number_is_optional(): void
    {
        Notification::fake();

        $this->postJson(route('auth.register.client'), $this->payload(['phone' => null]))
            ->assertCreated()
            ->assertJsonPath('data.user.client_profile.phone', null);
    }

    public function test_a_verification_email_is_sent_after_registration(): void
    {
        Notification::fake();

        $this->postJson(route('auth.register.client'), $this->payload())->assertCreated();

        Notification::assertSentTo(
            User::whereEmail('grace@acme-corp.com')->firstOrFail(),
            VerifyEmail::class,
        );
    }

    public function test_registration_rejects_a_duplicate_email(): void
    {
        User::factory()->create(['email' => 'grace@acme-corp.com']);

        $this->postJson(route('auth.register.client'), $this->payload())
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_registration_requires_a_company_name(): void
    {
        $this->postJson(route('auth.register.client'), $this->payload(['company_name' => '']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('company_name');
    }

    public function test_registration_rejects_an_invalid_phone_number(): void
    {
        $this->postJson(route('auth.register.client'), $this->payload(['phone' => 'not-a-phone']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('phone');
    }

    public function test_nothing_is_persisted_when_registration_fails(): void
    {
        $this->postJson(route('auth.register.client'), $this->payload(['company_name' => '']))
            ->assertStatus(422);

        $this->assertDatabaseCount('users', 0);
        $this->assertSame(0, ClientProfile::count());
    }
}
