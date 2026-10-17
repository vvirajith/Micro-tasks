<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthenticatedSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authenticated_user_can_fetch_their_own_account(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson(route('auth.me'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.email', $user->email)
            ->assertJsonMissingPath('data.user.password');
    }

    public function test_a_client_gets_their_company_profile_from_me(): void
    {
        $user = User::factory()->client()->create();
        Sanctum::actingAs($user);

        $this->getJson(route('auth.me'))
            ->assertOk()
            ->assertJsonPath('data.user.client_profile.company_name', $user->clientProfile->company_name);
    }

    public function test_protected_routes_reject_unauthenticated_requests(): void
    {
        foreach ([
            ['getJson', route('auth.me')],
            ['postJson', route('auth.logout')],
            ['putJson', route('profile.update')],
            ['putJson', route('auth.password.change')],
            ['getJson', route('worker.workspace')],
        ] as [$method, $url]) {
            $this->{$method}($url)
                ->assertStatus(401)
                ->assertJsonPath('success', false)
                ->assertJsonPath('message', 'Unauthenticated.');
        }
    }

    public function test_an_invalid_token_is_rejected(): void
    {
        $this->withHeader('Authorization', 'Bearer not-a-real-token')
            ->getJson(route('auth.me'))
            ->assertStatus(401);
    }

    /**
     * Guards against Laravel's default guest redirect, which resolves
     * route('login') and would turn this into a 500 in an API-only app.
     */
    public function test_a_protected_route_answers_401_even_when_the_client_does_not_ask_for_json(): void
    {
        $this->withHeaders(['Accept' => 'text/html'])
            ->get(route('auth.me'))
            ->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_logout_revokes_only_the_current_token(): void
    {
        $user = User::factory()->create([
            'email' => 'worker@example.com',
            'password' => 'Password123',
        ]);

        $tokenA = $this->postJson(route('auth.login'), [
            'email' => 'worker@example.com',
            'password' => 'Password123',
        ])->json('data.token');

        $this->postJson(route('auth.login'), [
            'email' => 'worker@example.com',
            'password' => 'Password123',
        ])->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 2);

        $this->withHeader('Authorization', "Bearer {$tokenA}")
            ->postJson(route('auth.logout'))
            ->assertOk()
            ->assertJsonPath('message', 'Logout successful');

        $this->assertDatabaseCount('personal_access_tokens', 1);

        // The revoked token no longer works.
        $this->forgetAuthenticatedUser();

        $this->withHeader('Authorization', "Bearer {$tokenA}")
            ->getJson(route('auth.me'))
            ->assertStatus(401);

        $this->assertSame(1, $user->tokens()->count());
    }

    public function test_logging_out_of_all_devices_revokes_every_token(): void
    {
        $user = User::factory()->create([
            'email' => 'worker@example.com',
            'password' => 'Password123',
        ]);

        $token = $this->postJson(route('auth.login'), [
            'email' => 'worker@example.com',
            'password' => 'Password123',
        ])->json('data.token');

        $this->postJson(route('auth.login'), [
            'email' => 'worker@example.com',
            'password' => 'Password123',
        ])->assertOk();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson(route('auth.logout.all'))
            ->assertOk();

        $this->assertSame(0, $user->tokens()->count());
    }
}
