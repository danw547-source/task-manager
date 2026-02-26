<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Contracts\View\View;

/**
 * Serves server-rendered blog pages backed by tasks.
 * Kept separate from API controllers because it returns Blade views instead of JSON.
 */
class BlogController extends Controller
{
    public function index(): View
    {
        $posts = Task::query()
            ->with('user:id,name')
            ->latest()
            ->paginate(8);

        return view('blog.index', compact('posts'));
    }

    public function show(Task $task): View
    {
        $task->load('user:id,name');

        return view('blog.show', [
            'post' => $task,
        ]);
    }
}
