<?php

namespace App\Http\Controllers\V1\Library;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProcurementModeResource;
use App\Services\ProcurementModeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Library - Procurement Modes
 * APIs for managing procurement modes
 */
class ProcurementModeController extends Controller
{
    public function __construct(
        protected ProcurementModeService $service
    ) {}

    /**
     * List Procurement Modes
     *
     * Retrieve a paginated list of procurement modes.
     *
     * @queryParam search string Search by mode name.
     * @queryParam per_page int Number of items per page. Default 50.
     * @queryParam show_all boolean Show all items without pagination. Default false.
     * @queryParam show_inactive boolean Show inactive procurement modes. Default false.
     * @queryParam column_sort string Sort field. Default mode_name.
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

        $procurementModes = $this->service->getAll($filters);

        return ProcurementModeResource::collection($procurementModes);
    }

    /**
     * Create Procurement Mode
     *
     * Create a new procurement mode.
     *
     * @bodyParam mode_name string required The mode name.
     * @bodyParam active boolean required Whether the procurement mode is active. Default true.
     *
     * @response 201 {
     *   "data": {
     *     "id": "uuid",
     *     "mode_name": "Competitive Bidding"
     *   },
     *   "message": "Mode of procurement created successfully."
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mode_name' => 'required|unique:procurement_modes,mode_name',
            'active' => 'required|boolean',
        ]);

        try {
            $procurementMode = $this->service->create($validated);

            return response()->json([
                'data' => new ProcurementModeResource($procurementMode),
                'message' => 'Mode of procurement created successfully.',
            ], 201);
        } catch (\Throwable $th) {
            $this->service->logError('Mode of procurement creation failed.', $th, $validated);

            return response()->json([
                'message' => 'Mode of procurement creation failed. Please try again.',
            ], 422);
        }
    }

    /**
     * Show Procurement Mode
     *
     * Get a specific procurement mode by ID.
     *
     * @urlParam id string required The procurement mode UUID.
     *
     * @response 200 {
     *   "data": {
     *     "id": "uuid",
     *     "mode_name": "Competitive Bidding"
     *   }
     * }
     */
    public function show(string $id): JsonResponse
    {
        $procurementMode = $this->service->getById($id);

        if (! $procurementMode) {
            return response()->json(['message' => 'Mode of procurement not found.'], 404);
        }

        return response()->json([
            'data' => new ProcurementModeResource($procurementMode),
        ]);
    }

    /**
     * Update Procurement Mode
     *
     * Update an existing procurement mode.
     *
     * @urlParam id string required The procurement mode UUID.
     *
     * @bodyParam mode_name string required The mode name.
     * @bodyParam active boolean required Whether the procurement mode is active.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Mode of procurement updated successfully."
     * }
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'mode_name' => 'required|unique:procurement_modes,mode_name,'.$id,
            'active' => 'required|boolean',
        ]);

        try {
            $procurementMode = $this->service->update($id, $validated);

            return response()->json([
                'data' => new ProcurementModeResource($procurementMode),
                'message' => 'Mode of procurement updated successfully.',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Mode of procurement update failed.', $th, $validated);

            return response()->json([
                'message' => 'Mode of procurement update failed. Please try again.',
            ], 422);
        }
    }
}
