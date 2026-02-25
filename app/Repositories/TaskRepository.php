<?php

namespace App\Repositories;
use App\Models\Task;
use App\Repositories\TaskRepositoryInterface;

class TaskRepository implements TaskRepositoryInterface
{
    public function all(?string $status = null, int $page = 1, int $perPage = 12)
    {
        return Task::with('user:id,name,email')
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderBy('position')
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function find($id){
        return Task::find($id);
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
