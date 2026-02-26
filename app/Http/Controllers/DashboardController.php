<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dashboard\SummaryRequest;
use App\Services\DashboardService;
use App\Traits\ApiResponse;

/**
 * Returns dashboard analytics endpoints.
 * The controller stays lightweight and passes data work to the service layer.
 */
class DashboardController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly DashboardService $dashboardService)
    {
    }

    public function summary(SummaryRequest $request)
    {
        $payload = $request->validated();

        $months = (int) ($payload['months'] ?? 12);
        $ownerId = $request->user()?->isAdmin() && isset($payload['user_id'])
            ? (int) $payload['user_id']
            : null;

        $summary = $this->dashboardService->summary($months, $ownerId);

        return $this->successResponse($summary, 'Dashboard summary retrieved successfully');
    }
}
