<?php

namespace App\Services;

use App\Repositories\DashboardRepositoryInterface;

/**
 * Builds dashboard summary use cases.
 * This keeps controllers independent from query details.
 */
class DashboardService
{
    public function __construct(private readonly DashboardRepositoryInterface $repository)
    {
    }

    public function summary(int $months = 12, ?int $ownerId = null): array
    {
        return $this->repository->summary($months, $ownerId);
    }
}
