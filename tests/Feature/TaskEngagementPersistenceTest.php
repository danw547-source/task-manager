<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskEngagementPersistenceTest extends TestCase
{
    use RefreshDatabase;

    // Smoke test proving a normal user can create a task and it is persisted with correct owner.
    public function test_task_creation_is_persisted(): void
    {
        /** @var User $actor */
        $actor = User::factory()->create(['role' => 'user']);
        $this->actingAs($actor, 'api');

        $response = $this->postJson('/api/v1/tasks', [
            'title' => 'Persisted Task',
            'description' => 'created via feature test',
            'status' => 'pending',
            'due_date' => now()->addDays(2)->toDateString(),
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Persisted Task',
            'user_id' => $actor->id,
        ]);
    }

    // Ensures follow edges are written to the pivot table used by follow-based filtering.
    public function test_follow_action_is_persisted(): void
    {
        /** @var User $owner */
        $owner = User::factory()->create();
        /** @var User $follower */
        $follower = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($follower, 'api');

        $response = $this->postJson("/api/v1/tasks/{$task->id}/follow");

        $response->assertOk();

        $this->assertDatabaseHas('task_follows', [
            'task_id' => $task->id,
            'user_id' => $follower->id,
        ]);
    }

    // Verifies comment writes hit storage so conversation threads can be rebuilt after reload.
    public function test_comment_action_is_persisted(): void
    {
        /** @var User $owner */
        $owner = User::factory()->create();
        /** @var User $commenter */
        $commenter = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($commenter, 'api');

        $response = $this->postJson("/api/v1/tasks/{$task->id}/comments", [
            'body' => 'This comment should be stored',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('task_comments', [
            'task_id' => $task->id,
            'user_id' => $commenter->id,
            'body' => 'This comment should be stored',
        ]);
    }
}
