<?php

namespace App\Repositories;

use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Builds dashboard metrics using query-level aggregation.
 * The repository keeps data-access concerns out of services and controllers.
 */
class DashboardRepository implements DashboardRepositoryInterface
{
    public function summary(int $months = 12, ?int $ownerId = null): array
    {
        $months = max(1, min(24, $months));

        $periodStart = Carbon::now()->startOfMonth()->subMonths($months - 1);
        $periodEnd = Carbon::now()->endOfMonth();

        $monthBuckets = collect(range(0, $months - 1))
            ->map(function (int $offset) use ($periodStart) {
                $month = (clone $periodStart)->addMonths($offset);

                return [
                    'key' => $month->format('Y-m'),
                    'label' => strtoupper($month->format('M')),
                ];
            })
            ->values();

        $totalTasks = Task::query()
            ->when($ownerId, fn ($query) => $query->where('user_id', $ownerId))
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->count();

        $completedTasks = Task::query()
            ->when($ownerId, fn ($query) => $query->where('user_id', $ownerId))
            ->where('status', 'done')
            ->whereBetween('updated_at', [$periodStart, $periodEnd])
            ->count();

        $tasksByMonth = Task::query()
            ->selectRaw("strftime('%Y-%m', created_at) as month_key")
            ->selectRaw('COUNT(*) as total')
            ->when($ownerId, fn ($query) => $query->where('user_id', $ownerId))
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->groupBy('month_key')
            ->pluck('total', 'month_key');

        $completedByMonth = Task::query()
            ->selectRaw("strftime('%Y-%m', updated_at) as month_key")
            ->selectRaw('COUNT(*) as total')
            ->when($ownerId, fn ($query) => $query->where('user_id', $ownerId))
            ->where('status', 'done')
            ->whereBetween('updated_at', [$periodStart, $periodEnd])
            ->groupBy('month_key')
            ->pluck('total', 'month_key');

        $doneTasks = Task::query()
            ->select(['created_at', 'updated_at', 'due_date'])
            ->when($ownerId, fn ($query) => $query->where('user_id', $ownerId))
            ->where('status', 'done')
            ->whereBetween('updated_at', [$periodStart, $periodEnd])
            ->get();

        $durationByMonth = [];

        foreach ($doneTasks as $task) {
            $monthKey = Carbon::parse($task->updated_at)->format('Y-m');
            $createdAt = Carbon::parse($task->created_at);
            $updatedAt = Carbon::parse($task->updated_at);

            $actualDays = max(0, $createdAt->diffInSeconds($updatedAt, false) / 86400);
            $estimatedDays = $task->due_date
                ? max(0, $createdAt->diffInSeconds(Carbon::parse($task->due_date), false) / 86400)
                : 0;

            if (!isset($durationByMonth[$monthKey])) {
                $durationByMonth[$monthKey] = [
                    'estimated_sum' => 0,
                    'actual_sum' => 0,
                    'count' => 0,
                ];
            }

            $durationByMonth[$monthKey]['estimated_sum'] += $estimatedDays;
            $durationByMonth[$monthKey]['actual_sum'] += $actualDays;
            $durationByMonth[$monthKey]['count'] += 1;
        }

        $estimatedSeries = [];
        $actualSeries = [];
        $taskTotalsSeries = [];
        $completedSeries = [];

        foreach ($monthBuckets as $bucket) {
            $monthKey = $bucket['key'];
            $count = $durationByMonth[$monthKey]['count'] ?? 0;

            $estimatedSeries[] = $count > 0
                ? round($durationByMonth[$monthKey]['estimated_sum'] / $count, 2)
                : 0;
            $actualSeries[] = $count > 0
                ? round($durationByMonth[$monthKey]['actual_sum'] / $count, 2)
                : 0;

            $taskTotalsSeries[] = (int) ($tasksByMonth[$monthKey] ?? 0);
            $completedSeries[] = (int) ($completedByMonth[$monthKey] ?? 0);
        }

        $completionRate = $totalTasks > 0
            ? round(($completedTasks / $totalTasks) * 100, 1)
            : 0.0;

        $leaderboard = DB::table('users')
            ->leftJoin('tasks', function ($join) use ($periodStart, $periodEnd, $ownerId) {
                $join->on('tasks.user_id', '=', 'users.id')
                    ->when($ownerId, fn ($query) => $query->where('tasks.user_id', $ownerId))
                    ->whereBetween('tasks.created_at', [$periodStart, $periodEnd]);
            })
            ->when($ownerId, fn ($query) => $query->where('users.id', $ownerId))
            ->select('users.name', 'users.email')
            ->selectRaw('COUNT(tasks.id) as tasks_count')
            ->selectRaw("SUM(CASE WHEN tasks.status = 'done' THEN 1 ELSE 0 END) as completed_count")
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('tasks_count')
            ->orderBy('users.name')
            ->get()
            ->map(function ($row) {
                $tasksCount = (int) $row->tasks_count;
                $completedCount = (int) ($row->completed_count ?? 0);

                return [
                    'name' => $row->name,
                    'email' => $row->email,
                    'tasks' => $tasksCount,
                    'completed' => $completedCount,
                    'outstanding' => max(0, $tasksCount - $completedCount),
                ];
            })
            ->values()
            ->all();

        return [
            'labels' => $monthBuckets->pluck('label')->all(),
            'performance' => [
                'estimated_completion_days' => $estimatedSeries,
                'actual_completion_days' => $actualSeries,
            ],
            'totals' => [
                'total_tasks' => $totalTasks,
                'completed_tasks' => $completedTasks,
                'completion_rate' => $completionRate,
            ],
            'monthly' => [
                'total_tasks' => $taskTotalsSeries,
                'completed_tasks' => $completedSeries,
            ],
            'leaderboard' => $leaderboard,
        ];
    }
}
