<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stores per-user reactions on tasks.
 * This keeps the task model focused on core task fields.
 */
class TaskReaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'user_id',
        'reaction',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
