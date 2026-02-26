<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserOptionsTest extends TestCase
{
    use RefreshDatabase;

    // Owner filter dropdown relies on this endpoint for all authenticated roles.
    public function test_authenticated_user_can_fetch_user_options(): void
    {
        /** @var User $viewer */
        $viewer = User::factory()->create(['role' => 'user']);
        /** @var User $listedUser */
        $listedUser = User::factory()->create(['name' => 'Alice Example']);

        $this->actingAs($viewer, 'api');

        $response = $this->getJson('/api/v1/users/options');

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonFragment([
            'id' => $listedUser->id,
            'name' => $listedUser->name,
        ]);
    }

    // User options remain private to authenticated sessions.
    public function test_guest_cannot_fetch_user_options(): void
    {
        $response = $this->getJson('/api/v1/users/options');

        $response->assertStatus(401);
    }
}
