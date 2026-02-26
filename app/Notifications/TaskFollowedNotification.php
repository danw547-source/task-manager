<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskFollowedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Task $task,
        private readonly User $follower
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New follower on your task')
            ->greeting('Hi '.$notifiable->name.',')
            ->line($this->follower->name.' just followed your task: "'.$this->task->title.'".')
            ->line('They will now receive updates and comments for this task.')
            ->action('Open Task Manager', config('app.url'))
            ->line('Thanks for using Task Manager!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'follower_id' => $this->follower->id,
            'follower_name' => $this->follower->name,
        ];
    }
}
