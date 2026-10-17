<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccountStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_suspended_user_cannot_reach_protected_routes(): void
    {
        $user = User::factory()->status(UserStatus::Suspended)->create();
        Sanctum::actingAs($user);

        $this->getJson(route('auth.me'))
            ->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', UserStatus::Suspended->denialMessage());
    }

    public function test_a_blocked_users_tokens_are_revoked_on_access(): void
    {
        $user = User::factory()->create([
            'email' => 'worker@example.com',
            'password' => 'Password123',
        ]);

        $token = $this->postJson(route('auth.login'), [
            'email' => 'worker@example.com',
            'password' => 'Password123',
        ])->json('data.token');

        $user->forceFill(['status' => UserStatus::Blocked])->save();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson(route('auth.me'))
            ->assertStatus(403)
            ->assertJsonPath('message', UserStatus::Blocked->denialMessage());

        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_a_suspended_users_tokens_are_kept(): void
    {
        $user = User::factory()->status(UserStatus::Suspended)->create();
        $user->createToken('test');

        Sanctum::actingAs($user);
        $this->getJson(route('auth.me'))->assertStatus(403);

        $this->assertSame(1, $user->tokens()->count());
    }

    public function test_a_pending_user_keeps_access_to_their_own_account(): void
    {
        $user = User::factory()->status(UserStatus::Pending)->create();
        Sanctum::actingAs($user);

        $this->getJson(route('auth.me'))->assertOk();
    }

    public function test_an_active_user_can_reach_protected_routes(): void
    {
        $user = User::factory()->status(UserStatus::Active)->create();
        Sanctum::actingAs($user);

        $this->getJson(route('auth.me'))->assertOk();
    }
}
