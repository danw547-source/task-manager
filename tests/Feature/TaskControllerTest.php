<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;

class TaskControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithoutMiddleware;

    public function test_index_filters_tasks_by_status(): void
    {
        Task::factory()->create(['status' => 'pending']);
        Task::factory()->create(['status' => 'done']);

        $response = $this->getJson('/tasks?status=pending');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.status', 'pending');
    }

    public function test_store_creates_task_with_assigned_user(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/tasks', [
            'title' => 'Assigned task',
            'description' => 'Needs owner',
            'status' => 'pending',
            'due_date' => now()->addDay()->toDateString(),
            'user_id' => $user->id,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('tasks', [
            'title' => 'Assigned task',
            'user_id' => $user->id,
        ]);
    }

    public function test_reorder_updates_task_positions(): void
    {
        $taskA = Task::factory()->create(['position' => 1]);
        $taskB = Task::factory()->create(['position' => 2]);
        $taskC = Task::factory()->create(['position' => 3]);

        $response = $this->postJson('/tasks/reorder', [
            'ordered_ids' => [$taskC->id, $taskA->id, $taskB->id],
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('tasks', ['id' => $taskC->id, 'position' => 1]);
        $this->assertDatabaseHas('tasks', ['id' => $taskA->id, 'position' => 2]);
        $this->assertDatabaseHas('tasks', ['id' => $taskB->id, 'position' => 3]);
    }
}
