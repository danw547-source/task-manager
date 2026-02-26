<?php

namespace App\Repositories;

/**
 * Defines dashboard aggregate query methods.
 * The interface lets us swap implementations without touching services.
 */
interface DashboardRepositoryInterface
{
    public function summary(int $months = 12, ?int $ownerId = null): array;
}
