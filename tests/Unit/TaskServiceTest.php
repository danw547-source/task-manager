<?php

namespace Tests\Unit;

use App\Repositories\TaskRepositoryInterface;
use App\Services\TaskService;
use PHPUnit\Framework\TestCase;

class TaskServiceTest extends TestCase
{
    public function test_it_gets_all_tasks_with_optional_status(): void
    {
        $repo = $this->createMock(TaskRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('all')
            ->with('pending', 1, 12)
            ->willReturn([]);

        $service = new TaskService($repo);

        $this->assertSame([], $service->getAllTasks('pending', 1, 12));
    }

    public function test_it_reorders_tasks(): void
    {
        $repo = $this->createMock(TaskRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('reorder')
            ->with([3, 1, 2])
            ->willReturn([]);

        $service = new TaskService($repo);

        $this->assertSame([], $service->reorderTasks([3, 1, 2]));
    }
}
