<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_filters_tasks_by_status(): void
    {
        /** @var User $actor */
        $actor = User::factory()->create(['role' => 'user']);
        $this->actingAs($actor, 'api');

        Task::factory()->create(['status' => 'pending']);
        Task::factory()->create(['status' => 'done']);

        $response = $this->getJson('/api/v1/tasks?status=pending');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.status', 'pending');
    }

    public function test_store_creates_task_with_assigned_user(): void
    {
        /** @var User $actor */
        $actor = User::factory()->create(['role' => 'admin']);
        $this->actingAs($actor, 'api');

        $user = User::factory()->create();

        $response = $this->postJson('/api/v1/tasks', [
            'title' => 'Assigned task',
            'description' => 'Needs owner',
            'status' => 'pending',
            'due_date' => now()->addDay()->toDateString(),
            'user_id' => $user->id,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Assigned task',
            'user_id' => $user->id,
        ]);
    }

    public function test_store_requires_description_and_due_date_and_rejects_past_due_date(): void
    {
        /** @var User $actor */
        $actor = User::factory()->create(['role' => 'user']);
        $this->actingAs($actor, 'api');

        $responseMissingFields = $this->postJson('/api/v1/tasks', [
            'title' => 'Missing fields task',
        ]);

        $responseMissingFields->assertStatus(422);
        $responseMissingFields->assertJsonValidationErrors(['description', 'due_date']);

        $responsePastDate = $this->postJson('/api/v1/tasks', [
            'title' => 'Past due date task',
            'description' => 'Has description',
            'due_date' => now()->subDay()->toDateString(),
        ]);

        $responsePastDate->assertStatus(422);
        $responsePastDate->assertJsonValidationErrors(['due_date']);
    }

    public function test_reorder_updates_task_positions(): void
    {
        /** @var User $actor */
        $actor = User::factory()->create(['role' => 'user']);
        $this->actingAs($actor, 'api');

        $taskA = Task::factory()->create(['position' => 1]);
        $taskB = Task::factory()->create(['position' => 2]);
        $taskC = Task::factory()->create(['position' => 3]);

        $response = $this->postJson('/api/v1/tasks/reorder', [
            'ordered_ids' => [$taskC->id, $taskA->id, $taskB->id],
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('tasks', ['id' => $taskC->id, 'position' => 1]);
        $this->assertDatabaseHas('tasks', ['id' => $taskA->id, 'position' => 2]);
        $this->assertDatabaseHas('tasks', ['id' => $taskB->id, 'position' => 3]);
    }

    public function test_update_changes_task_title_description_and_due_date(): void
    {
        /** @var User $owner */
        $owner = User::factory()->create(['role' => 'admin']);
        $task = Task::factory()->create([
            'user_id' => $owner->id,
            'title' => 'Original title',
            'description' => 'Original description',
            'due_date' => now()->addDay()->toDateString(),
        ]);

        $this->actingAs($owner, 'api');

        $newDueDate = now()->addDays(5)->toDateString();

        $response = $this->putJson("/api/v1/tasks/{$task->id}", [
            'title' => 'Updated title',
            'description' => 'Updated description',
            'due_date' => $newDueDate,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.title', 'Updated title');
        $response->assertJsonPath('data.description', 'Updated description');
        $responseDueDate = (string) data_get($response->json(), 'data.due_date', '');
        $this->assertSame($newDueDate, substr($responseDueDate, 0, 10));

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated title',
            'description' => 'Updated description',
        ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'due_date' => $newDueDate . ' 00:00:00',
        ]);
    }

    public function test_update_rejects_invalid_due_date_format_and_past_due_date(): void
    {
        /** @var User $owner */
        $owner = User::factory()->create(['role' => 'admin']);
        $task = Task::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($owner, 'api');

        $invalidFormat = $this->putJson("/api/v1/tasks/{$task->id}", [
            'due_date' => '12/31/2026',
        ]);

        $invalidFormat->assertStatus(422);
        $invalidFormat->assertJsonValidationErrors(['due_date']);

        $pastDate = $this->putJson("/api/v1/tasks/{$task->id}", [
            'due_date' => now()->subDay()->toDateString(),
        ]);

        $pastDate->assertStatus(422);
        $pastDate->assertJsonValidationErrors(['due_date']);
    }

    public function test_store_ignores_user_id_for_non_admin_and_uses_actor_id(): void
    {
        /** @var User $actor */
        $actor = User::factory()->create(['role' => 'user']);
        $otherUser = User::factory()->create();

        $this->actingAs($actor, 'api');

        $response = $this->postJson('/api/v1/tasks', [
            'title' => 'Non-admin assignment attempt',
            'description' => 'Should always belong to actor',
            'status' => 'pending',
            'due_date' => now()->addDays(2)->toDateString(),
            'user_id' => $otherUser->id,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.user_id', $actor->id);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Non-admin assignment attempt',
            'user_id' => $actor->id,
        ]);
    }
}
