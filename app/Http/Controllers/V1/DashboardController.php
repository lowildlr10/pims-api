<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\DashboardResource;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @group Dashboard
 * APIs for retrieving dashboard statistics and workflow counts
 */
class DashboardController extends Controller
{
    public function __construct(protected DashboardService $service) {}

    /**
     * Get Dashboard Data
     *
     * Retrieve dashboard statistics including workflow counts for PR, PO, OBR, and DV.
     *
     * @response 200 {"data": {"active": 0, "pending_approval": 0, ...}}
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        $permissions = $user->tokens->flatMap(function ($token) {
            return $token->abilities;
        })->unique()->values()->toArray();

        $dashboardData = $this->service->getDashboardData($user->id, $permissions);

        return response()->json([
            'data' => new DashboardResource($dashboardData),
        ]);
    }
}
