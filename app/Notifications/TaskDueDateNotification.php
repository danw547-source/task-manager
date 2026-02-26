<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskDueDateNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Task $task)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Task due reminder')
            ->greeting('Hi '.$notifiable->name.',')
            ->line('This is a reminder about an upcoming task due date.')
            ->line('Task: '.$this->task->title)
            ->line('Due date: '.optional($this->task->due_date)->format('Y-m-d'))
            ->line('Status: '.$this->task->status);
    }
}
