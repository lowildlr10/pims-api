<?php

namespace App\Http\Controllers\V1\Library;

use App\Http\Controllers\Controller;
use App\Http\Resources\UnitIssueResource;
use App\Services\UnitIssueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Library - Unit Issues
 * APIs for managing unit issues
 */
class UnitIssueController extends Controller
{
    public function __construct(
        protected UnitIssueService $service
    ) {}

    /**
     * List Unit Issues
     *
     * Retrieve a paginated list of unit issues.
     *
     * @queryParam search string Search by unit name.
     * @queryParam per_page int Number of items per page. Default 50.
     * @queryParam show_all boolean Show all items without pagination. Default false.
     * @queryParam show_inactive boolean Show inactive unit issues. Default false.
     * @queryParam column_sort string Sort field. Default unit_name.
     * @queryParam sort_direction string Sort direction (asc/desc). Default desc.
     * @queryParam paginated boolean Return paginated results. Default true.
     *
     * @response 200 {
     *   "data": [...],
     *   "meta": {...}
     * }
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only([
            'search',
            'per_page',
            'show_all',
            'show_inactive',
            'column_sort',
            'sort_direction',
            'paginated',
        ]);

        $filters['show_all'] = filter_var($filters['show_all'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $filters['show_inactive'] = filter_var($filters['show_inactive'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $filters['paginated'] = filter_var($filters['paginated'] ?? true, FILTER_VALIDATE_BOOLEAN);

        $unitIssues = $this->service->getAll($filters);

        return UnitIssueResource::collection($unitIssues);
    }

    /**
     * Create Unit Issue
     *
     * Create a new unit of issue.
     *
     * @bodyParam unit_name string required The unit name.
     * @bodyParam active boolean required Whether the unit issue is active. Default true.
     *
     * @response 201 {
     *   "data": {
     *     "id": "uuid",
     *     "unit_name": "Piece"
     *   },
     *   "message": "Unit of issue created successfully."
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'unit_name' => 'required|unique:unit_issues,unit_name',
            'active' => 'required|boolean',
        ]);

        try {
            $unitIssue = $this->service->create($validated);

            return response()->json([
                'data' => new UnitIssueResource($unitIssue),
                'message' => 'Unit of issue created successfully.',
            ], 201);
        } catch (\Throwable $th) {
            $this->service->logError('Unit of issue creation failed.', $th, $validated);

            return response()->json([
                'message' => 'Unit of issue creation failed. Please try again.',
            ], 422);
        }
    }

    /**
     * Show Unit Issue
     *
     * Get a specific unit of issue by ID.
     *
     * @urlParam id string required The unit issue UUID.
     *
     * @response 200 {
     *   "data": {
     *     "id": "uuid",
     *     "unit_name": "Piece"
     *   }
     * }
     */
    public function show(string $id): JsonResponse
    {
        $unitIssue = $this->service->getById($id);

        if (! $unitIssue) {
            return response()->json(['message' => 'Unit of issue not found.'], 404);
        }

        return response()->json([
            'data' => new UnitIssueResource($unitIssue),
        ]);
    }

    /**
     * Update Unit Issue
     *
     * Update an existing unit of issue.
     *
     * @urlParam id string required The unit issue UUID.
     *
     * @bodyParam unit_name string required The unit name.
     * @bodyParam active boolean required Whether the unit issue is active.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Unit of issue updated successfully."
     * }
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'unit_name' => 'required|unique:unit_issues,unit_name,'.$id,
            'active' => 'required|boolean',
        ]);

        try {
            $unitIssue = $this->service->update($id, $validated);

            return response()->json([
                'data' => new UnitIssueResource($unitIssue),
                'message' => 'Unit of issue updated successfully.',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Unit of issue update failed.', $th, $validated);

            return response()->json([
                'message' => 'Unit of issue update failed. Please try again.',
            ], 422);
        }
    }
}
