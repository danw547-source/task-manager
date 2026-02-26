<?php

namespace App\Repositories;
use App\Models\Task;
use Illuminate\Support\Facades\DB;
use App\Repositories\TaskRepositoryInterface;

/**
 * Eloquent-backed task repository.
 * Query shaping lives here so callers get consistent task payloads.
 */
class TaskRepository implements TaskRepositoryInterface
{
    public function all(?string $status = null, int $page = 1, int $perPage = 12, ?int $viewerId = null, ?int $ownerId = null)
    {
        $paginated = Task::with('user:id,name,email')
            ->withCount([
                'followers',
                'comments',
            ])
            ->when($ownerId, fn ($query) => $query->where('user_id', $ownerId))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderBy('position')
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', $page);

        if ($viewerId) {
            $taskIds = $paginated->getCollection()->pluck('id')->all();

            $followedTaskIds = DB::table('task_follows')
                ->where('user_id', $viewerId)
                ->whereIn('task_id', $taskIds)
                ->pluck('task_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $followedIndex = array_flip($followedTaskIds);

            // We annotate each task for frontend convenience while keeping DB reads O(1) queries.
            $paginated->getCollection()->transform(function ($task) use ($followedIndex) {
                $task->is_following = isset($followedIndex[(int) $task->id]);

                return $task;
            });
        }

        return $paginated;
    }

    public function find($id){
        return Task::with([
                'user:id,name,email',
                'comments.user:id,name,email',
            ])
            ->withCount([
                'followers',
                'comments',
            ])
            ->find($id);
    }
    public function create(array $data)
    {
        if (!array_key_exists('position', $data)) {
            $data['position'] = ((int) Task::max('position')) + 1;
        }

        return Task::create($data)->load('user:id,name,email');
    }

    public function update($id, array $data)
    {
        $task = Task::findOrFail($id);
        $task->update($data);

        return $task->load('user:id,name,email');
    }

    public function delete($id)
    {
        $task = Task::findOrFail($id);

        return $task->delete();
    }

    public function reorder(array $orderedIds)
    {
        foreach ($orderedIds as $index => $id) {
            Task::whereKey($id)->update(['position' => $index + 1]);
        }

        return Task::with('user:id,name,email')
            ->orderBy('position')
            ->orderByDesc('created_at')
            ->get();
    }

}
