<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_update_their_basic_profile(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'country_code' => 'GB',
            'preferred_language' => 'en',
        ]);
        Sanctum::actingAs($user);

        $this->putJson(route('profile.update'), [
            'first_name' => 'Augusta',
            'last_name' => 'King',
            'country_code' => 'us',
            'preferred_language' => 'pt-BR',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Profile updated successfully')
            ->assertJsonPath('data.user.first_name', 'Augusta')
            ->assertJsonPath('data.user.country_code', 'US')
            ->assertJsonPath('data.user.preferred_language', 'pt-BR');

        $this->assertSame('Augusta King', $user->fresh()->full_name);
    }

    public function test_partial_updates_leave_other_fields_untouched(): void
    {
        $user = User::factory()->create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);
        Sanctum::actingAs($user);

        $this->putJson(route('profile.update'), ['first_name' => 'Augusta'])->assertOk();

        $this->assertSame('Lovelace', $user->fresh()->last_name);
    }

    public function test_the_email_address_cannot_be_changed_through_the_profile(): void
    {
        $user = User::factory()->create(['email' => 'ada@example.com']);
        Sanctum::actingAs($user);

        $this->putJson(route('profile.update'), [
            'first_name' => 'Augusta',
            'email' => 'attacker@example.com',
        ])->assertOk();

        $this->assertSame('ada@example.com', $user->fresh()->email);
    }

    public function test_the_role_and_status_cannot_be_changed_through_the_profile(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->putJson(route('profile.update'), [
            'first_name' => 'Augusta',
            'role' => 'admin',
            'status' => 'suspended',
        ])->assertOk();

        $fresh = $user->fresh();
        $this->assertTrue($fresh->isWorker());
        $this->assertSame($user->status, $fresh->status);
    }

    public function test_a_client_can_update_their_company_details(): void
    {
        $user = User::factory()->client()->create();
        Sanctum::actingAs($user);

        $this->putJson(route('profile.update'), [
            'company_name' => 'New Horizons Ltd',
            'phone' => '+442071234567',
        ])
            ->assertOk()
            ->assertJsonPath('data.user.client_profile.company_name', 'New Horizons Ltd')
            ->assertJsonPath('data.user.client_profile.phone', '+442071234567');
    }

    public function test_company_details_are_ignored_for_workers(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->putJson(route('profile.update'), [
            'company_name' => 'Should Be Ignored',
        ])->assertOk();

        $this->assertNull($user->fresh()->clientProfile()->first());
    }

    public function test_profile_updates_are_validated(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->putJson(route('profile.update'), [
            'first_name' => 'A',
            'country_code' => 'USA',
            'preferred_language' => 'not a language',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['first_name', 'country_code', 'preferred_language']);
    }

    public function test_updating_a_profile_requires_authentication(): void
    {
        $this->putJson(route('profile.update'), ['first_name' => 'Augusta'])
            ->assertStatus(401);
    }
}
