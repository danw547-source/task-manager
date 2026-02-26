<?php

namespace App\Repositories;
use App\Models\Task;
use Illuminate\Support\Facades\DB;
use App\Repositories\TaskRepositoryInterface;

/**
 * Eloquent-backed task repository.
 * Handles persistence and query filtering for task reads/writes.
 */
class TaskRepository implements TaskRepositoryInterface
{
    public function all(
        ?string $status = null,
        int $page = 1,
        int $perPage = 12,
        ?int $viewerId = null,
        ?int $ownerId = null,
        string $scope = 'all'
    )
    {
        // Guard against unexpected values if a caller bypasses request validation.
        $scope = in_array($scope, ['all', 'owned', 'following'], true) ? $scope : 'all';

        $paginated = Task::with('user:id,name,email')
            ->withCount([
                'followers',
                'comments',
            ])
            // `owned`: show only tasks created by the current viewer.
            ->when($viewerId && $scope === 'owned', fn ($query) => $query->where('user_id', $viewerId))
            // `following`: only tasks explicitly followed by viewer, excluding owned tasks.
            ->when($viewerId && $scope === 'following', function ($query) use ($viewerId) {
                $query
                    ->where('user_id', '!=', $viewerId)
                    ->whereExists(function ($subQuery) use ($viewerId) {
                        $subQuery->selectRaw('1')
                            ->from('task_follows')
                            ->whereColumn('task_follows.task_id', 'tasks.id')
                            ->where('task_follows.user_id', $viewerId);
                    });
            })
            ->when($ownerId, fn ($query) => $query->where('user_id', $ownerId))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderBy('position')
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', $page);

        return $paginated;
    }

    public function followingTaskIds(int $userId, array $taskIds): array
    {
        // Service uses this lightweight lookup to annotate page results with
        // viewer-specific follow state without changing core task query shape.
        if (empty($taskIds)) {
            return [];
        }

        return DB::table('task_follows')
            ->where('user_id', $userId)
            ->whereIn('task_id', $taskIds)
            ->pluck('task_id')
            ->map(fn ($id) => (int) $id)
            ->all();
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
