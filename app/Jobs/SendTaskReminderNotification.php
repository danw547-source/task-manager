<?php

namespace App\Jobs;

use App\Models\Task;
use App\Notifications\TaskDueDateNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendTaskReminderNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function handle(): void
    {
        // Send the notification to the task's assigned user
        if ($this->task->user) {
            $this->task->user->notify(new TaskDueDateNotification($this->task));
        }
    }
}
