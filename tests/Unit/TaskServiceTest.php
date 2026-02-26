<?php

namespace Tests\Unit;

use App\Repositories\TaskRepositoryInterface;
use App\Services\TaskService;
use PHPUnit\Framework\TestCase;

class TaskServiceTest extends TestCase
{
    // Service should pass filter arguments through unchanged to repository query layer.
    public function test_it_gets_all_tasks_with_optional_status(): void
    {
        $repo = $this->createMock(TaskRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('all')
            ->with('pending', 1, 12, null, null, 'all')
            ->willReturn([]);

        $service = new TaskService($repo);

        $this->assertSame([], $service->all('pending', 1, 12));
    }

    // Reorder is orchestration-only in service and must delegate directly.
    public function test_it_reorders_tasks(): void
    {
        $repo = $this->createMock(TaskRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('reorder')
            ->with([3, 1, 2])
            ->willReturn([]);

        $service = new TaskService($repo);

        $this->assertSame([], $service->reorder([3, 1, 2]));
    }
}
