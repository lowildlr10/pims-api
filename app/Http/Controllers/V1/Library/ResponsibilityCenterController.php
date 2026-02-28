<?php

namespace App\Http\Controllers\V1\Library;

use App\Http\Controllers\Controller;
use App\Http\Resources\ResponsibilityCenterResource;
use App\Services\ResponsibilityCenterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Library - Responsibility Centers
 * APIs for managing responsibility centers
 */
class ResponsibilityCenterController extends Controller
{
    public function __construct(protected ResponsibilityCenterService $service) {}

    /**
     * List Responsibility Centers
     *
     * @queryParam search string Search by code or description.
     * @queryParam per_page int Number of items per page. Default 50.
     * @queryParam show_all boolean Show all items. Default false.
     * @queryParam show_inactive boolean Show inactive. Default false.
     * @queryParam column_sort string Sort field. Default code.
     * @queryParam sort_direction string Sort direction. Default desc.
     * @queryParam paginated boolean Return paginated results. Default true.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['search', 'per_page', 'show_all', 'show_inactive', 'column_sort', 'sort_direction', 'paginated']);
        $filters['show_all'] = filter_var($filters['show_all'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $filters['show_inactive'] = filter_var($filters['show_inactive'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $filters['paginated'] = filter_var($filters['paginated'] ?? true, FILTER_VALIDATE_BOOLEAN);

        return ResponsibilityCenterResource::collection($this->service->getAll($filters));
    }

    /**
     * Create Responsibility Center
     *
     * @bodyParam code string required The code.
     * @bodyParam description string optional The description.
     * @bodyParam active boolean required Whether active. Default true.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|unique:responsibility_centers,code',
            'description' => 'nullable|string',
            'active' => 'required|boolean',
        ]);
        try {
            $responsibilityCenter = $this->service->create($validated);

            return response()->json([
                'data' => new ResponsibilityCenterResource($responsibilityCenter),
                'message' => 'Responsibility center created successfully.',
            ], 201);
        } catch (\Throwable $th) {
            $this->service->logError('Responsibility center creation failed.', $th, $validated);

            return response()->json(['message' => 'Responsibility center creation failed. Please try again.'], 422);
        }
    }

    /**
     * Show Responsibility Center
     *
     * @urlParam id string required The UUID.
     */
    public function show(string $id): JsonResponse
    {
        $responsibilityCenter = $this->service->getById($id);
        if (! $responsibilityCenter) {
            return response()->json(['message' => 'Responsibility center not found.'], 404);
        }

        return response()->json(['data' => new ResponsibilityCenterResource($responsibilityCenter)]);
    }

    /**
     * Update Responsibility Center
     *
     * @urlParam id string required The UUID.
     *
     * @bodyParam code string required The code.
     * @bodyParam description string optional The description.
     * @bodyParam active boolean required Whether active.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|unique:responsibility_centers,code,'.$id,
            'description' => 'nullable|string',
            'active' => 'required|boolean',
        ]);
        try {
            $responsibilityCenter = $this->service->update($id, $validated);

            return response()->json([
                'data' => new ResponsibilityCenterResource($responsibilityCenter),
                'message' => 'Responsibility center updated successfully.',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Responsibility center update failed.', $th, $validated);

            return response()->json(['message' => 'Responsibility center update failed. Please try again.'], 422);
        }
    }
}
