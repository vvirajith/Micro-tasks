<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string, UserRole}>
     */
    public static function roleAreas(): array
    {
        return [
            'worker area' => ['worker.workspace', UserRole::Worker],
            'client area' => ['client.workspace', UserRole::Client],
            'admin area' => ['admin.workspace', UserRole::Admin],
        ];
    }

    #[DataProvider('roleAreas')]
    public function test_the_matching_role_is_granted_access(string $route, UserRole $role): void
    {
        $user = $role === UserRole::Client
            ? User::factory()->client()->create()
            : User::factory()->create(['role' => $role]);

        Sanctum::actingAs($user);

        $this->getJson(route($route))
            ->assertOk()
            ->assertJsonPath('data.role', $role->value);
    }

    #[DataProvider('roleAreas')]
    public function test_other_roles_are_denied_access(string $route, UserRole $role): void
    {
        foreach (UserRole::cases() as $otherRole) {
            if ($otherRole === $role) {
                continue;
            }

            $user = $otherRole === UserRole::Client
                ? User::factory()->client()->create()
                : User::factory()->create(['role' => $otherRole]);

            Sanctum::actingAs($user);

            $this->getJson(route($route))
                ->assertStatus(403)
                ->assertJsonPath('success', false)
                ->assertJsonPath('message', 'This action is unauthorized for your account role.');
        }
    }

    public function test_role_areas_require_a_verified_email(): void
    {
        $user = User::factory()->unverified()->create(['role' => UserRole::Worker]);
        Sanctum::actingAs($user);

        $this->getJson(route('worker.workspace'))
            ->assertStatus(403)
            ->assertJsonPath('message', 'Your email address is not verified.');
    }

    public function test_role_areas_require_authentication(): void
    {
        $this->getJson(route('admin.workspace'))->assertStatus(401);
    }
}
