<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

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

    public function summary(Request $request)
    {
        $months = max(1, min(24, (int) $request->query('months', 12)));
        $ownerId = null;

        if ($request->user()?->isAdmin() && $request->filled('user_id')) {
            $payload = $request->validate([
                'user_id' => 'required|integer|exists:users,id',
            ]);

            $ownerId = (int) $payload['user_id'];
        }

        $summary = $this->dashboardService->summary($months, $ownerId);

        return $this->successResponse($summary, 'Dashboard summary retrieved successfully');
    }
}
