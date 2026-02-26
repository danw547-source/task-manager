<?php

namespace Database\Factories;

use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = fake()->randomElement(['pending', 'in_progress', 'done']);
        $dueDate = fake()->dateTimeBetween('+1 day', '+14 days')->format('Y-m-d');
        $title = fake()->randomElement([
            'Review quarterly roadmap milestones',
            'Prepare sprint planning notes',
            'Validate release readiness checklist',
            'Update API integration guide',
            'Refine onboarding task sequence',
            'Audit permission role mappings',
            'Coordinate stakeholder feedback review',
            'Finalize dashboard accessibility fixes',
            'Reconcile backlog priority changes',
            'Confirm deployment rollback steps',
            'Document support handoff process',
            'Plan customer follow-up actions',
            'Review incident response timeline',
            'Optimize task list query performance',
            'Verify mobile form validation flows',
        ]);

        return [
            'title' => $title,
            'description' => fake()->optional()->paragraph(),
            'status' => $status,
            'due_date' => $dueDate,
        ];
    }
}
