<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskControllerTest extends TestCase
{
    use RefreshDatabase;

    // Protects the basic list filtering contract used by the task table and dashboard cards.
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

    // Confirms admins can explicitly assign ownership during creation.
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

    // Captures core creation validation: task content is required and due dates cannot be in the past.
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

    // Verifies drag/drop ordering persists as explicit position values.
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

    // Due date is intentionally immutable after creation; edit flow only changes title/description.
    public function test_update_changes_task_title_and_description_without_changing_due_date(): void
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

        $existingDueDate = (string) $task->due_date;

        $response = $this->putJson("/api/v1/tasks/{$task->id}", [
            'title' => 'Updated title',
            'description' => 'Updated description',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.title', 'Updated title');
        $response->assertJsonPath('data.description', 'Updated description');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated title',
            'description' => 'Updated description',
        ]);

        $task->refresh();
        $this->assertSame(substr($existingDueDate, 0, 10), optional($task->due_date)->format('Y-m-d'));
    }

    // Guards the immutable due-date rule at the API boundary.
    public function test_update_rejects_due_date_changes(): void
    {
        /** @var User $owner */
        $owner = User::factory()->create(['role' => 'admin']);
        $task = Task::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($owner, 'api');

        $response = $this->putJson("/api/v1/tasks/{$task->id}", [
            'due_date' => now()->subDay()->toDateString(),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['due_date']);
    }

    // Normal users should still be able to browse by owner, even without admin privileges.
    public function test_non_admin_can_filter_tasks_by_owner_user_id(): void
    {
        /** @var User $actor */
        $actor = User::factory()->create(['role' => 'user']);
        $ownerA = User::factory()->create();
        $ownerB = User::factory()->create();

        $this->actingAs($actor, 'api');

        Task::factory()->create(['user_id' => $ownerA->id, 'title' => 'Owner A task']);
        Task::factory()->create(['user_id' => $ownerB->id, 'title' => 'Owner B task']);

        $response = $this->getJson("/api/v1/tasks?user_id={$ownerA->id}");

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.user_id', $ownerA->id);
    }

    // Owners are treated as following by default so frontend can rely on one follow-state flag.
    public function test_owner_is_marked_as_following_by_default_in_task_list(): void
    {
        /** @var User $owner */
        $owner = User::factory()->create(['role' => 'user']);
        $task = Task::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($owner, 'api');

        $response = $this->getJson('/api/v1/tasks');

        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $task->id,
            'is_following' => true,
        ]);
    }

    // Scope=owned should be strict and only include tasks created by the current user.
    public function test_scope_owned_returns_only_current_users_tasks(): void
    {
        /** @var User $owner */
        $owner = User::factory()->create(['role' => 'user']);
        /** @var User $other */
        $other = User::factory()->create(['role' => 'user']);

        Task::factory()->create(['user_id' => $owner->id, 'title' => 'Mine']);
        Task::factory()->create(['user_id' => $other->id, 'title' => 'Not mine']);

        $this->actingAs($owner, 'api');

        $response = $this->getJson('/api/v1/tasks?scope=owned');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.user_id', $owner->id);
    }

    // Scope=following is explicit follows only; owned tasks are intentionally excluded.
    public function test_scope_following_returns_only_explicitly_followed_non_owned_tasks(): void
    {
        /** @var User $viewer */
        $viewer = User::factory()->create(['role' => 'user']);
        /** @var User $owner */
        $owner = User::factory()->create(['role' => 'user']);

        $ownedTask = Task::factory()->create(['user_id' => $viewer->id, 'title' => 'Owned task']);
        $followedTask = Task::factory()->create(['user_id' => $owner->id, 'title' => 'Followed task']);
        $notFollowedTask = Task::factory()->create(['user_id' => $owner->id, 'title' => 'Not followed']);

        DB::table('task_follows')->insert([
            'task_id' => $followedTask->id,
            'user_id' => $viewer->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($viewer, 'api');

        $response = $this->getJson('/api/v1/tasks?scope=following');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonMissing(['id' => $ownedTask->id]);
        $response->assertJsonMissing(['id' => $notFollowedTask->id]);
        $response->assertJsonPath('data.0.id', $followedTask->id);
    }

    // Prevents privilege escalation where a non-admin attempts to assign tasks to someone else.
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
