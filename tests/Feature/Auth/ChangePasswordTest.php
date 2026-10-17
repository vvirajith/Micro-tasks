<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{User, string}
     */
    private function loggedInUser(): array
    {
        $user = User::factory()->create([
            'email' => 'worker@example.com',
            'password' => 'Password123',
        ]);

        $token = $this->postJson(route('auth.login'), [
            'email' => 'worker@example.com',
            'password' => 'Password123',
        ])->json('data.token');

        return [$user, $token];
    }

    public function test_an_authenticated_user_can_change_their_password(): void
    {
        [$user, $token] = $this->loggedInUser();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson(route('auth.password.change'), [
                'current_password' => 'Password123',
                'password' => 'NewPassword456',
                'password_confirmation' => 'NewPassword456',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertTrue(Hash::check('NewPassword456', $user->fresh()->password));
    }

    public function test_the_current_password_must_be_correct(): void
    {
        [$user, $token] = $this->loggedInUser();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson(route('auth.password.change'), [
                'current_password' => 'WrongPassword123',
                'password' => 'NewPassword456',
                'password_confirmation' => 'NewPassword456',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('current_password');

        $this->assertTrue(Hash::check('Password123', $user->fresh()->password));
    }

    public function test_the_new_password_must_be_confirmed(): void
    {
        [, $token] = $this->loggedInUser();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson(route('auth.password.change'), [
                'current_password' => 'Password123',
                'password' => 'NewPassword456',
                'password_confirmation' => 'Mismatch456',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    public function test_the_new_password_must_differ_from_the_current_one(): void
    {
        [, $token] = $this->loggedInUser();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson(route('auth.password.change'), [
                'current_password' => 'Password123',
                'password' => 'Password123',
                'password_confirmation' => 'Password123',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    public function test_other_sessions_are_revoked_but_the_current_one_survives(): void
    {
        [$user, $token] = $this->loggedInUser();

        // A second device.
        $this->postJson(route('auth.login'), [
            'email' => 'worker@example.com',
            'password' => 'Password123',
        ])->assertOk();

        $this->assertSame(2, $user->tokens()->count());

        $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson(route('auth.password.change'), [
                'current_password' => 'Password123',
                'password' => 'NewPassword456',
                'password_confirmation' => 'NewPassword456',
            ])->assertOk();

        $this->assertSame(1, $user->tokens()->count());

        $this->forgetAuthenticatedUser();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson(route('auth.me'))
            ->assertOk();
    }

    public function test_changing_a_password_requires_authentication(): void
    {
        $this->putJson(route('auth.password.change'), [
            'current_password' => 'Password123',
            'password' => 'NewPassword456',
            'password_confirmation' => 'NewPassword456',
        ])->assertStatus(401);
    }
}
