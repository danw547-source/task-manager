<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Notifications\TaskDueDateNotification;
use Illuminate\Console\Command;

class SendTaskDueNotifications extends Command
{
    protected $signature = 'tasks:notify-due';

    protected $description = 'Send notifications for tasks due today or tomorrow';

    public function handle(): int
    {
        $tasks = Task::query()
            ->with('user')
            ->whereIn('status', ['pending', 'in_progress'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', now()->addDay()->toDateString())
            ->get();

        $sent = 0;

        foreach ($tasks as $task) {
            if ($task->user && $task->user->email) {
                $task->user->notify(new TaskDueDateNotification($task));
                $sent++;
            }
        }

        $this->info("Sent {$sent} due-date notifications.");

        return self::SUCCESS;
    }
}
