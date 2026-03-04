<?php

namespace App\Http\Controllers\V1\Library;

use App\Http\Controllers\Controller;
use App\Http\Resources\ItemClassificationResource;
use App\Services\ItemClassificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Library - Item Classifications
 * APIs for managing item classifications
 */
class ItemClassificationController extends Controller
{
    public function __construct(protected ItemClassificationService $service) {}

    /**
     * List Item Classifications
     *
     * @queryParam search string Search by classification name.
     * @queryParam per_page int Number of items per page. Default 50.
     * @queryParam show_all boolean Show all items. Default false.
     * @queryParam show_inactive boolean Show inactive. Default false.
     * @queryParam column_sort string Sort field. Default classification_name.
     * @queryParam sort_direction string Sort direction. Default desc.
     * @queryParam paginated boolean Return paginated results. Default true.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['search', 'per_page', 'show_all', 'show_inactive', 'column_sort', 'sort_direction', 'paginated']);
        $filters['show_all'] = filter_var($filters['show_all'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $filters['show_inactive'] = filter_var($filters['show_inactive'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $filters['paginated'] = filter_var($filters['paginated'] ?? true, FILTER_VALIDATE_BOOLEAN);

        return ItemClassificationResource::collection($this->service->getAll($filters));
    }

    /**
     * Create Item Classification
     *
     * @bodyParam classification_name string required The classification name.
     * @bodyParam active boolean required Whether active. Default true.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'classification_name' => 'required|unique:item_classifications,classification_name',
            'active' => 'required|boolean',
        ]);
        try {
            $itemClassification = $this->service->create($validated);

            return response()->json([
                'data' => new ItemClassificationResource($itemClassification),
                'message' => 'Item classification created successfully.',
            ], 201);
        } catch (\Throwable $th) {
            $this->service->logError('Item classification creation failed.', $th, $validated);

            return response()->json(['message' => 'Item classification creation failed. Please try again.'], 422);
        }
    }

    /**
     * Show Item Classification
     *
     * @urlParam id string required The UUID.
     */
    public function show(string $id): JsonResponse
    {
        $itemClassification = $this->service->getById($id);
        if (! $itemClassification) {
            return response()->json(['message' => 'Item classification not found.'], 404);
        }

        return response()->json(['data' => new ItemClassificationResource($itemClassification)]);
    }

    /**
     * Update Item Classification
     *
     * @urlParam id string required The UUID.
     *
     * @bodyParam classification_name string required The classification name.
     * @bodyParam active boolean required Whether active.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'classification_name' => 'required|unique:item_classifications,classification_name,'.$id,
            'active' => 'required|boolean',
        ]);
        try {
            $itemClassification = $this->service->update($id, $validated);

            return response()->json([
                'data' => new ItemClassificationResource($itemClassification),
                'message' => 'Item classification updated successfully.',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Item classification update failed.', $th, $validated);

            return response()->json(['message' => 'Item classification update failed. Please try again.'], 422);
        }
    }
}
